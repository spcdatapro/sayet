(function () {

    var rptrecclimenctrl = angular.module('cpm.rptfinanzas', []);

    rptrecclimenctrl.controller('rptFinanzas', ['$scope', 'jsReportSrvc', 'empleadoSrvc',
        function ($scope, jsReportSrvc, empleadoSrvc) {

            // $scope.empleados = [];

            // empleadoSrvc.lstEmpleados().then(function (d) { $scope.empleados = d; });

            // $scope.params = {
            //     fdel: moment().startOf('month').toDate(), 
            //     fal: moment().endOf('month').toDate(),                
            //     idempleado: undefined
            // };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getPDF = function () {
                // $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
                // $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
                // $scope.params.idempleado = $scope.params.idempleado != null && $scope.params.idempleado != undefined ? $scope.params.idempleado : 0;
                var rpttest = 'BkiHRn_g3', rpt = 'BkiHRn_g3';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());