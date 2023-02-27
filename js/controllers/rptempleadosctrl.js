(function () {

    var controller = angular.module('cpm.rptempleados', []);

    controller.controller('rptEmpleados', ['$scope', 'jsReportSrvc', 'empresaSrvc',
        function ($scope, jsReportSrvc, empresaSrvc) {

            $scope.empresas = [];

            empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

            $scope.params = {
                // fdel: moment().startOf('month').toDate(), 
                // fal: moment().endOf('month').toDate(),                
                idempleado: undefined,
                inactivos: 0
            };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getLibSalarios = function () {
                // $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
                // $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
                $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
                $scope.params.inactivos = $scope.params.inactivos;
                var rpttest = 'BydmBd9To', rpt = 'S1C2VP90j';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());