(function () {

    var controller = angular.module('cpm.docsbancoctrl', []);

    controller.controller('docsBancoCtrl', ['$scope', 'tranBancSrvc', 'bancoSrvc', '$interval', 'toaster', 'tipoMovTranBanSrvc',
        ($scope, tranBancSrvc, bancoSrvc, $interval, toaster, tipoMovTranBanSrvc) => {
            $scope.documentos = []; // desde se almacenan todos los documentos
            $scope.todas = [];
            $scope.cargando = false; // si esta cargando 
            $scope.progress = 0; // progreso de carga
            // Obtener la fecha de hoy menos un día, o viernes si hoy es lunes
            let fecha = moment().toDate();
            let diaSemana = moment(fecha).day();

            if (diaSemana === 0 || diaSemana === 6) { // Lunes
                fdelFal = moment().subtract(3, 'days').toDate(); // Viernes anterior
            } else {
                fdelFal = moment().subtract(1, 'days').toDate(); // Día anterior
            }
            $scope.params = { ver: 3, fdel: fdelFal, fal: fdelFal, reporte: false };

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

            bancoSrvc.lstBancosActivos(4).then(d => {
                // solo mostrar los dos bancos que estan utilizando mt940
                let idbancos = ['3', '33'];
                $scope.bancos = d.filter(banco => idbancos.includes(banco.id));
            })

            $scope.tipotrans = [{ id: '1', abreviadesc: '(C) Créditos', abreviatura: 'C' },
            { id: '2', abreviadesc: '(D) Débitos', abreviatura: 'D' }];

            $scope.buscarDocumentos = () => {
                $scope.cargando = true;
                $scope.params.delstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                $scope.params.alstr = moment($scope.params.fal).format('YYYY-MM-DD');

                // $scope.params.delstr = '20250501';
                // $scope.params.alstr = '20250501';

                estatusCarga(40);

                // primero nos concetcamos al banco, para traer los documentos
                tranBancSrvc.concectarBanco()
                    .then(d => {
                        $scope.progress = 40;
                        toaster.pop({ type: d.tipo, title: 'Conexion a banco', body: d.mensaje, timeout: 10000 })

                        if (d.tipo === 'success') {
                            estatusCarga(80);

                            // si la conxeion es exitosa, entonces buscamos documetos para conciliar
                            tranBancSrvc.traerDocumentos($scope.params)
                                .then(d => {
                                    $scope.progress = 100;
                                    $scope.cargando = false;

                                    console.log(d);
                                    $scope.todas = d.bancos;
                                    $scope.documentos = d.bancos;
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

                        console.log(d);
                        $scope.todas = d.bancos;
                        $scope.documentos = d.bancos;
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
                    }
                });

                const url = window.location.origin + ':5489/api/report';
                let props = {}, file, formData = new FormData();

                const promises = aimprimir.map(tran => {
                    props = { 'template': { 'shortid': 'ryTzo2NGge' }, 'data': { idnota: tran.id, iniciales: $scope.params.iniciales } };
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

            $scope.$watch('selTodos', function (newVal, oldVal) {
                if (newVal != oldVal) {
                    $scope.documentos.forEach(f => f.conciliar = +newVal);
                }
            });

            function estatusCarga(limite) {
                $interval(() => {
                    if ($scope.progress < limite) {
                        $scope.progress += 1;
                    } else {
                        return;
                    }
                }, 500)
            }

        }]);
}());
