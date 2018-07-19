(function(){

    var rptplnpremiosctrl = angular.module('cpm.rptplnpremiosctrl', []);

    rptplnpremiosctrl.controller('rptPlnPremiosCtrl', ['$scope', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', function($scope, empresaSrvc, authSrvc, jsReportSrvc){

        $scope.params = { del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate() };

        var test = false;
        $scope.getReporte = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'ryyL-BA7Q' : 'ryyL-BA7Q', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
