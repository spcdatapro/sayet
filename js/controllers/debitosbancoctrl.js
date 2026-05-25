(function () {

    var controller = angular.module('cpm.debitosbanco', []);

    controller.controller('debitosBancoCtrl', ['$scope', 'tranBancSrvc', 'bancoSrvc', '$interval', 'toaster', '$http', '$q', '$window', 'authSrvc', 'empresaSrvc', 'tipoMovTranBanSrvc',
        ($scope, tranBancSrvc, bancoSrvc, $interval, toaster, $http, $q, $window, authSrvc, empresaSrvc, tipoMovTranBanSrvc) => {
            $scope.documentos = []; // desde se almacenan todos los documentos
            $scope.todas = [];
            $scope.cargando = false; // si esta cargando 
            $scope.progress = 0; // progreso de carga
            $scope.iniciales = 'N/E';
            $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), reporte: false, ver: '0' };
            $scope.idbanco = undefined;
            $scope.todos = true;

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
                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(user.uid).then(autorizado => {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });
                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                        $scope.params.idempresa = user.workingon.toString();
                    });
                });
            })

            traerBancos = idempresa => {
                bancoSrvc.lstBancosMT940(idempresa).then(d => { $scope.bancos = d });
            }

            tipoMovTranBanSrvc.getBySuma(false).then(d => { $scope.tipotrans = d });

            $scope.buscarDocumentos = () => {
                $scope.cargando = true;
                delstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                alstr = moment($scope.params.fal).format('YYYY-MM-DD');
                traerBancos($scope.params.idempresa);

                estatusCarga(40);

                // primero nos concetcamos al banco, para traer los documentos
                tranBancSrvc.concectarBanco()
                    .then(d => {
                        $scope.progress = 40;
                        toaster.pop({ type: d.tipo, title: 'Conexion a banco', body: d.mensaje, timeout: 10000 })

                        if (d.tipo === 'success' || d.tipo === 'warning') {
                            estatusCarga(80);

                            // si la conxeion es exitosa, entonces buscamos documetos para conciliar
                            tranBancSrvc.emparejarDebitos(delstr, alstr, $scope.params.idempresa)
                                .then(d => {
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

                                    $scope.progress = 100;
                                    $scope.cargando = false;

                                    $scope.todas = transacciones;
                                    $scope.documentos = transacciones;
                                })
                        }
                    })
            }

            $scope.buscar = () => {
                delstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                alstr = moment($scope.params.fal).format('YYYY-MM-DD');
                traerBancos($scope.params.idempresa);
                // si la conxeion es exitosa, entonces buscamos documetos para conciliar
                tranBancSrvc.emparejarDebitos(delstr, alstr, $scope.params.idempresa)
                    .then(d => {
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

                        $scope.progress = 100;
                        $scope.cargando = false;

                        $scope.todas = transacciones;
                        // $scope.documentos = transacciones;

                    if ($scope.params.idbanco > 0 || $scope.params.tipos || $scope.params.ver) {
                            $scope.documentos = $scope.todas.filter(tran => {
                                let matchesBanco = $scope.params.idbanco > 0 ? tran.idbanco.toString() === $scope.params.idbanco.toString() : true;
                                let matchesTipo = !$scope.params.tipos ? true : tran.tipotrans === $scope.params.tipos;
                                let matchesVer = !$scope.params.ver ? true :
                                    ($scope.params.ver == 1 ? tran.emparejado == 0 :
                                        $scope.params.ver == 2 ? tran.emparejado == 1 : true);

                                return matchesBanco && matchesTipo && matchesVer;
                            });
                        }
                    })
            }

            $scope.$watchGroup(['params.idbanco', 'params.tipos', 'params.ver'], function (newVals) {
                let idbanco = newVals[0];
                let tipos = newVals[1];
                let ver = newVals[2];

                $scope.documentos = $scope.todas.filter(tran => {
                    let matchesBanco = idbanco > 0 ? tran.idbanco.toString() === idbanco.toString() : true;
                    let matchesTipo = !tipos ? true : tran.tipotrans === tipos;
                    let matchesVer = !ver ? true :
                        (ver == 1 ? tran.emparejado == 0 :
                            ver == 2 ? tran.emparejado == 1 : true);

                    return matchesBanco && matchesTipo && matchesVer;
                });

                $scope.currentPage = 1;
            });

            $scope.imprimirNota = () => {
                let aimprimir = [];
                $scope.paginatedEmpleados().forEach(tran => {
                    if (tran.conciliar == 1) {
                        aimprimir.push(tran);
                        tran.impreso = +1;
                    }
                });

                const url = `${window.location.origin}/api/report`;
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
                        const urlpdf = window.location.origin + '/php/pdfgenerator/OTs.pdf';
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
                $scope.paginatedEmpleados().forEach(tran => {
                    if (tran.conciliar == 1 && tran.emparejado == 0) {
                        aemparejar.push(tran);
                        tran.impreso = +1;
                    }
                })

                if (aemparejar.length > 0) {
                    tranBancSrvc.emparejarDebitosBanco(aemparejar).then(d => {
                        toaster.pop({ type: d.tipo, title: 'Emparejar débitos', body: d.mensaje, timeout: 5000 });
                        $scope.buscar();
                    });
                } else {
                    toaster.pop({ type: 'error', title: 'Emparejar débitos', body: 'No hay débitos seleccionados para emparejar.', timeout: 5000 });
                    return;
                }
            }

            $scope.selTodos = todos => {
                $scope.paginatedEmpleados().forEach(tran => {
                    if (tran.cuantos === 1) {
                        tran.conciliar = todos;
                    }
                });
            }

        }]);
}());
