(function(){

    var rptivaventactrl = angular.module('cpm.rptivaventactrl', []);

    rptivaventactrl.controller('rptIVAVentasCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){

        $scope.params = { mes: (moment().month() + 1).toString(), anio: moment().year() };

        $scope.geIvaVentas = function(){
            var test = false;
            jsReportSrvc.getPDFReport(test ? '' : 'HJilbOKoZ', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());