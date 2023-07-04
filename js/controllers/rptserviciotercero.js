(function () {

    var reportectrl = angular.module('cpm.rptservterceros', []);

    reportectrl.controller('rptServTerceros', ['$scope', 'jsReportSrvc', 'servicioBasicoSrvc',
        function ($scope, jsReportSrvc, servicioBasicoSrvc) {

            $scope.servicios = [];
            $scope.params = { idservicio: undefined, anio: undefined };

            servicioBasicoSrvc.getContadores(0).then(function (r) { $scope.servicios = r; });

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getPDF = function () {
                var rpttest = 'rk2i3Ct_3', rpt = 'rk2i3Ct_3';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

            $scope.resetParams = function () {
                $scope.params = { idservicio: undefined, anio: undefined };
            }

        }]);
}());