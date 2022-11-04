(function () {

    var rptrecclimenctrl = angular.module('cpm.rptcontcrea', []);

    rptrecclimenctrl.controller('rptContratosCreados', ['$scope', 'jsReportSrvc', 'empresaSrvc',
        function ($scope, jsReportSrvc, empresaSrvc) {

            $scope.empresas = [];

            empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

            $scope.params = {
                anio: moment().year(), idempresa: undefined
            };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getRptContratos = function () {
                $scope.params.anio;
                $scope.params.idempresa;
                var rpttest = 'rkKYL3WBj', rpt = 'rkKYL3WBj';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());