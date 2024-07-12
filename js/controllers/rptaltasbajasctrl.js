(function () {

    var controller = angular.module('cpm.rptaltasbajas', []);

    controller.controller('rptAltasBajas', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'proyectoSrvc', function ($scope, 
        jsReportSrvc, empresaSrvc, proyectoSrvc) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];

            // estatus de carga
            $scope.cargando = false;

            // parametros para reporte
            $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), tipo: '3' };

            // para visualizaciones en pantalla
            $scope.ver = { resumen: false };
            $scope.content = `${window.location.origin}/sayet/blank.html`;

            // traer empresas
            empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

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

                console.log(params); return;

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
        }]);
}());