(function () {

    var controller = angular.module('cpm.debitosbanco', []);

    controller.controller('debitosBancoCtrl', ['$scope', 'tranBancSrvc', 'bancoSrvc', '$interval', 'toaster', '$http', '$q', '$window', 'authSrvc', 'desktopNotification',
        ($scope, tranBancSrvc, bancoSrvc, $interval, toaster, $http, $q, $window, authSrvc, desktopNotification) => {
            $scope.documentos = []; // desde se almacenan todos los documentos
            $scope.todas = [];
            $scope.cargando = false; // si esta cargando 
            $scope.progress = 0; // progreso de carga
            $scope.iniciales = 'N/E';
            $scope.params = { ver: 1, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), reporte: false, tipos: ['2'] };

            // para paginar
            $scope.currentPage = 1; // Página actual
            $scope.itemsPerPage = '20'; // Número de elementos por página
            $scope.lookFor = ''; // Busqueda

            // para pginas de resultados
            $scope.$watchGroup(['documentos.length', 'lookFor', 'itemsPerPage'], function () {
                $scope.totalPages = Math.ceil($scope.filteredEmpleados().length / $scope.itemsPerPage);
                $scope.currentPage = 1;
            });

            $scope.setPage = function (page) {
                if (page >= 1 && page <= $scope.totalPages) {
                    $scope.currentPage = page;
                }
            };

            $scope.paginatedEmpleados = function () {
                var filtered = $scope.filteredEmpleados();
                var start = ($scope.currentPage - 1) * +$scope.itemsPerPage;
                return filtered.slice(start, start + +$scope.itemsPerPage);
            };

            $scope.filteredEmpleados = function () {
                return $scope.documentos.filter(function (e) {
                    return !$scope.lookFor || Object.keys(e).some(function (key) {
                        return String(e[key]).toLowerCase().includes($scope.lookFor.toLowerCase());
                    });
                });
            };

            $scope.totalPages = Math.ceil($scope.documentos.length / +$scope.itemsPerPage);
            // fin de paginas

            var user = {};

            authSrvc.getSession().then(function (usrLogged) {
                user = usrLogged;
                $scope.params.iniciales = usrLogged.iniciales;
                bancoSrvc.lstBancosMT940(usrLogged.workingon).then(d => { $scope.bancos = d });
            })

            $scope.tipotrans = [{ id: '1', abreviadesc: '(C) Créditos', abreviatura: 'C' },
            { id: '2', abreviadesc: '(D) Débitos', abreviatura: 'D' }];

            $scope.buscarDocumentos = () => {
                $scope.cargando = true;
                delstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                alstr = moment($scope.params.fal).format('YYYY-MM-DD');

                estatusCarga(40);

                // primero nos concetcamos al banco, para traer los documentos
                tranBancSrvc.concectarBanco()
                    .then(d => {
                        $scope.progress = 40;
                        toaster.pop({ type: d.tipo, title: 'Conexion a banco', body: d.mensaje, timeout: 10000 })

                        if (d.tipo === 'success' || d.tipo === 'warning') {
                            estatusCarga(80);

                            // si la conxeion es exitosa, entonces buscamos documetos para conciliar
                            tranBancSrvc.emparejarDebitos(delstr, alstr, user.workingon)
                                .then(d => {
                                    console.log(d);
                                    let transacciones = d.map(tran => {
                                        // Clonamos el primer match
                                        let principal = { ...tran.matches[0] };
                                        principal.cuantos = tran.matches.length;
                                        
                                        if (tran.matches.length === 1) {
                                            if (+tran.matches[0].emparejado === 0) {
                                                principal.conciliar = +1;
                                            }
                                        }

                                        if (tran.matches.length > 1) {
                                            // Clonamos todos los matches SIN docs
                                            principal.docs = tran.matches.map(m => {
                                                let copia = { ...m };
                                                delete copia.docs;   // aseguramos que no se meta docs dentro de docs
                                                return copia;
                                            });
                                        }

                                        return principal;
                                    });

                                    console.log(transacciones);

                                    $scope.progress = 100;
                                    $scope.cargando = false;

                                    $scope.todas = transacciones;
                                    $scope.documentos = transacciones;
                                })
                        }
                    })
            }

            $scope.buscar = () => {
                $scope.params.delstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                $scope.params.alstr = moment($scope.params.fal).format('YYYY-MM-DD');
                tranBancSrvc.traerDocumentos($scope.params)
                    .then(d => {
                        $scope.progress = 100;
                        $scope.cargando = false;

                        $scope.todas = d.bancos;
                        $scope.documentos = d.bancos.filter(tran => tran.idtipotrans == 1);
                    })
            }

            $scope.$watchGroup(['params.idbanco', 'params.tipos', 'params.del', 'params.al'], function (newVals) {
                let [idbanco, tipos, del, al] = newVals;

                $scope.documentos = $scope.todas.filter(tran => {
                    let fecha = tran.concilia ? moment(tran.concilia).toDate() : null;
                    let afterDel = del ? fecha >= del : true;
                    let beforeAl = al ? fecha <= al : true;
                    let matchesBanco = idbanco > 0 ? tran.idempresa.toString() === idbanco.toString() : true;
                    let matchesTipos = tipos.length > 0 ? tipos.includes(tran.idtipotrans) : true;

                    return afterDel && beforeAl && matchesBanco && matchesTipos;
                });

                $scope.currentPage = 1;
            })

            $scope.imprimirNota = () => {
                let aimprimir = [];
                $scope.documentos.forEach(tran => {
                    if (tran.conciliar == 1) {
                        aimprimir.push(tran);
                        tran.impreso = +1;
                    }
                });

                const url = window.location.origin + ':5489/api/report';
                let props = {}, file, formData = new FormData();

                const promises = aimprimir.map(tran => {
                    props = { 'template': { 'shortid': 'ryTzo2NGge' }, 'data': { idnota: tran.id_banco, iniciales: $scope.params.iniciales } };
                    return $http.post(url, props, { responseType: 'arraybuffer' });
                });
                $q.all(promises).then((respuestas) => {
                    for (let i = 0; i < aimprimir.length; i++) {
                        file = new Blob([respuestas[i].data], { type: 'application/pdf' });
                        formData.append(`OT_${+aimprimir[i].id}`, file);
                    }

                    $.ajax({
                        url: "php/rptotgroup.php",
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: () => { },
                        error: () => console.log("Se produjo un error al generar la impresión de OTs...")
                    }).done(() => {
                        const urlpdf = window.location.origin + '/sayet/php/pdfgenerator/OTs.pdf';
                        $window.open(urlpdf);
                    });
                });
            }

            function estatusCarga(limite) {
                $interval(() => {
                    if ($scope.progress < limite) {
                        $scope.progress += 1;
                    } else {
                        return;
                    }
                }, 500)
            }

            $scope.emparejar = () => {
                let aemparejar = [];
                $scope.documentos.forEach(tran => {
                    if (tran.conciliar == 1) {
                        aemparejar.push(tran);
                        tran.impreso = +1;
                    }
                })

                console.log(aemparejar); 

                if (aemparejar.length > 0) {
                    tranBancSrvc.emparejarDebitosBanco(aemparejar).then(d => { 
                        toaster.pop({ type: d.tipo, title: 'Emparejar débitos', body: d.mensaje, timeout: 5000 });
                    });
                } else {
                    toaster.pop({ type: 'error', title: 'Emparejar débitos', body: 'No hay débitos seleccionados para emparejar.', timeout: 5000 });
                    return;
                }
            }

        }]);
}());
