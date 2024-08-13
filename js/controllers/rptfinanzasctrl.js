(function () {

    var rptrecclimenctrl = angular.module('cpm.rptfinanzas', []);

    rptrecclimenctrl.controller('rptFinanzas', ['$scope', 'jsReportSrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', 'unidadSrvc',
        '$filter', 'gerencialSrvc', 'localStorageSrvc', '$location', function ($scope, jsReportSrvc, authSrvc, empresaSrvc,
            proyectoSrvc, unidadSrvc, $filter, gerencialSrvc, localStorageSrvc, $location) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.unidades = [];
            $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
                'Noviembre', 'Diciembre'];

            // estatus de carga
            $scope.cargando = false;
            $scope.usuario = undefined;

            // parametros para reporte
            $scope.params = {
                mesdel: moment().toDate().getMonth().toString(), mesal: moment().toDate().getMonth().toString(),
                anio: +moment().toDate().getFullYear()
            };

            // variable que guarda el reporte para mostrar en pantalla
            $scope.reporte = [];

            // para visualizaciones en pantalla
            $scope.ver = { resumen: false };
            $scope.content = `${window.location.origin}/sayet/blank.html`;

            // asignar la empresa en la que el usuario se encuentra
            authSrvc.getSession().then(function (usuario) {

                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });

                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    });
                });

                // globalizar usuario
                $scope.usuario = usuario.uid;
                // asignar empresa
                $scope.params.idempresa = usuario.workingon.toString();
                // traer proyectos con la empresa del usuario
                $scope.getProyectos(usuario.workingon.toString());
            });

            // traer proyectos al cambiar empresa
            $scope.getProyectos = function (idempresa) {
                proyectoSrvc.lstProyectosPorEmpresa(idempresa, $scope.usuario).then(function (d) { $scope.proyectos = d; });
                $scope.params.idproyecto = undefined;
                $scope.params.idunidad = undefined;
            };

            // traer unidades al cambiar proyecto
            $scope.getUnidades = function (idproyecto) {
                unidadSrvc.lstUnidadesProy(idproyecto).then(function (d) { $scope.unidades = d; });
                $scope.params.idunidad = undefined;
            };

            // reporte en pantalla
            $scope.getResumen = function (params) {
                // estatus de carga
                $scope.cargando = true;
                // reinciar cualquier visualizacion
                resetVer();

                // para nombres en pantalla
                $scope.proyecto = $filter('getById')($scope.proyectos, params.idproyecto).nomproyecto;
                $scope.empresa = $filter('getById')($scope.empresas, params.idempresa).nomempresa;

                gerencialSrvc.finanzas(params).then(function (d) {
                    d.meses.forEach(data => {
                        data.diferencia = format(data.diferencia);
                    });
                    $scope.reporte = d.meses;
                    $scope.ver.resumen = true;
                    $scope.cargando = false;
                });
            }

            // pdf
            $scope.getPDF = function (params) {
                // estatus de carga
                $scope.cargando = true;
                // reinciar visualizacion
                resetVer();

                jsReportSrvc.getPDFReport('BkiHRn_g3', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            // excel
            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('r11kFGvUA', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;

                    if (params.mesdel == params.mesal) {
                        rango = (params.mesdel + 1) + '_' + params.anio;
                    } else {
                        rango = (params.mesdel + 1) + '_' + (params.mesal + 1) + '_' + params.anio;
                    }

                    saveAs(file, 'Reporte_Ingresos_Egresos_' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            };

            // para ver la factura que seleccionen
            $scope.verFactura = function (idfactura, origen) {
                // 1 = factura de venta 1 = factura de compra
                if (origen == 1) {
                    localStorageSrvc.set('idfactura', idfactura);
                    $location.path('tranventa');
                } else {
                    if (idfactura != null) {
                        localStorageSrvc.set('idfactura', idfactura);
                        $location.path('tranfactcompra');
                    }
                }
            };

            // reinicar visualizacion
            function resetVer() {
                $scope.ver = { resumen: false };
                $scope.content = `${window.location.origin}/sayet/blank.html`;
            }

            // formatear numero negativos en parentesis
            function format(x) {
                if (x) {
                    // Redondea el valor a dos decimales
                    x = parseFloat(x).toFixed(2);
            
                    var pref = '',
                        suf = '';
                    if (parseFloat(x) < 0) {
                        x = x * -1;
                        pref = '(';
                        suf = ')';
                    }
                    return pref + x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + suf;
                }
            }
        }]);
}());