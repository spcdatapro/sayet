(function () {

    const controller = angular.module('cpm.rptfactingreso', []);

    controller.controller('rptFactIngresoCtrl', ['$scope', 'jsReportSrvc', 'authSrvc', 'empresaSrvc', 'gerencialSrvc',
        function ($scope, jsReportSrvc, authSrvc, empresaSrvc, gerencialSrvc) {

            // variables para selectores
            $scope.empresas = [];


            // estatus de carga
            $scope.cargando = false;
            $scope.usuario = undefined;

            // parametros para reporte
            $scope.params = { anio: +moment().toDate().getFullYear(), idempresa: [] };

            // variable que guarda el reporte para mostrar en pantalla
            $scope.encabezado = {};
            $scope.anios = [];

            // para visualizaciones en pantalla
            $scope.ver = false;
            $scope.content = `${window.location.origin}/blank.html`

            // asignar la empresa en la que el usuario se encuentra
            authSrvc.getSession().then(usuario => {

                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(d => {
                    empresaSrvc.getEmpresaUsuario(usuario.uid).then(autorizado => {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        })

                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    })
                })

                // globalizar usuario
                $scope.usuario = usuario.uid;
                // asignar empresa
                $scope.params.idempresa.push(usuario.workingon.toString());
            })

            // pdf
            $scope.getPDF = (params) => {
                // estatus de carga
                $scope.cargando = true;
                // reinciar visualizacion
                resetVer();

                jsReportSrvc.getPDFReport('ryDqghxsZg', params).then(pdf => {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            }

            // excel
            $scope.getXML = params => {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('SycMfI-hWl', params).then(result => {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;

                    saveAs(file, 'Reporte_Fact_Ingreso_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            }

            // reporte de pantalla
            $scope.getResumen = params => {
                // estatus de carga
                $scope.cargando = true;
                // reinciar visualizacion
                resetVer();
                try {
                    gerencialSrvc.ingresos(params).then(r => {
                        $scope.cargando = false;
                        $scope.empresa = r.encabezado.empresa;
                        $scope.meses = r.ingresos[0].totales_mes;
                        $scope.ingresos = r.ingresos;
                        $scope.ver = true;
                        console.log(r);
                    });
                } catch (error) {
                    $scope.cargando = false;
                }
            }

            $scope.detalleFacturas = proyecto => {
                if (!proyecto.detalle_facturas) {
                    // validar si ya se trajeron los clientes
                    if (proyecto.clientes && proyecto.clientes.length > 0) {
                        proyecto.detalle_facturas = true;
                    } else {
                        gerencialSrvc.detalleFactura($scope.params.anio, proyecto.id).then(d => {
                            proyecto.clientes = d;
                            proyecto.detalle_facturas = true;
                        });
                    }
                } else {
                    proyecto.detalle_facturas = false;
                }
            }

            $scope.detalleRecibos = proyecto => {
                if (!proyecto.detalle_recibos) {
                    // validar si ya se trajeron los recibos
                    if (proyecto.recibos && proyecto.recibos.length > 0) {
                        proyecto.detalle_recibos = true;
                    } else {
                        gerencialSrvc.detalleRecibo($scope.params.anio, proyecto.id).then(d => {
                            proyecto.recibos = d;
                            proyecto.detalle_recibos = true;
                        });
                    }
                } else {
                    proyecto.detalle_recibos = false;
                }
            }

            // reinicar visualizacion
            function resetVer() {
                $scope.ver = false;
                $scope.content = `${window.location.origin}/blank.html`
            }
        }])
}())