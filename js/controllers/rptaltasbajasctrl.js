(function () {

    var controller = angular.module('cpm.rptaltasbajas', []);

    controller.controller('rptAltasBajas', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'proyectoSrvc', 'authSrvc', function ($scope, 
        jsReportSrvc, empresaSrvc, proyectoSrvc, authSrvc) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];

            // estatus de carga
            $scope.cargando = false;

            // parametros para reporte
            $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), tipo: '3',
                agrupar: '1'
            };

            // para visualizaciones en pantalla
            $scope.ver = { resumen: false };
            $scope.content = `${window.location.origin}/sayet/blank.html`;

            authSrvc.getSession().then(function (usuario) {
                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function(d) { 
                    empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });
                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    }); 
                });
            });

            // traer proyectos al cambiar empresa
            $scope.getProyectos = function (idempresa) {
                proyectoSrvc.lstProyectosPorEmpresa(idempresa).then(function (d) { $scope.proyectos = d; });
                $scope.params.idproyecto = undefined;
            };

            // pdf
            $scope.getPDF = function (params) {
                // estatus de carga
                $scope.cargando = true;

                // ajustar fechas
                params.falstr = moment(params.fal).format('YYYY-MM-DD');
                params.fdelstr = moment(params.fdel).format('YYYY-MM-DD');

                jsReportSrvc.getPDFReport('SkGm8JJdR', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            // excel
            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                // ajustar fechas
                params.falstr = moment(params.fal).format('YYYY-MM-DD');
                params.fdelstr = moment(params.fdel).format('YYYY-MM-DD');

                jsReportSrvc.getReport('B1wOhgkOC', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;

                    if (params.fdel == params.fal) {
                        rango = params.fdelstr;
                    } else {
                        rango = params.fdelstr + '_' + params.falstr;
                    }

                    saveAs(file, 'Reporte_Altas_Bajas_' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            };
        }]);
}());