(function () {

    var controller = angular.module('cpm.rptfacttran', []);

    controller.controller('rptFactTran', ['$scope', 'jsReportSrvc', function ($scope, jsReportSrvc) {

            // estatus de carga
            $scope.cargando = false;

            // parametros para reporte
            $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate() };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            // pdf
            $scope.getPDF = function (params) {
                // estatus de carga
                $scope.cargando = true;

                // ajustar fechas
                params.falstr = moment(params.fal).format('YYYY-MM-DD');
                params.fdelstr = moment(params.fdel).format('YYYY-MM-DD');

                jsReportSrvc.getPDFReport('ryRIW4mKWg', params).then(function (pdf) {
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

                jsReportSrvc.getReport('HJGLL87K-g', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;

                    if (params.fdel == params.fal) {
                        rango = params.fdelstr;
                    } else {
                        rango = params.fdelstr + '_' + params.falstr;
                    }

                    saveAs(file, 'Rpoerte_Facturas_Transacciones' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            };
        }]);
}());