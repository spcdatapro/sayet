(function () {

    var controller = angular.module('cpm.aguinaldo', []);

    controller.controller('rptAguinaldo', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'proyectoSrvc', function ($scope, 
        jsReportSrvc, empresaSrvc, proyectoSrvc) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];

            // estatus de carga
            $scope.cargando = false;

            // parametros para reporte
            $scope.params = { anio: +moment().toDate().getFullYear().toString(), agrupar: '1' };

            // para visualizaciones en pantalla
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

                jsReportSrvc.getPDFReport('ryFYd5TuA', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            // excel
            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('SJiq_cTuR', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;

                    saveAs(file, 'Reporte_Aguinaldo_' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            };
        }]);
}());