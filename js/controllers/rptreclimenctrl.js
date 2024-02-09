(function () {

    var rptrecclimenctrl = angular.module('cpm.rptrecclimenctrl', []);

    rptrecclimenctrl.controller('rptRecibosClienteMenCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc',
        function ($scope, jsReportSrvc, empresaSrvc) {

            $scope.empresas = [];

            empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

            $scope.params = {
                fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(),                
                idempresa: [], tipo: '1'
            };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getRptRecibosCliente = function () {
                $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
                $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
                var rpttest = 'Sk_TXxlMQ', rpt = 'ryKtNbFFq';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());