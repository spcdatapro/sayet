(function(){

    var rptivaventactrl = angular.module('cpm.rptivaventactrl', []);

    rptivaventactrl.controller('rptIVAVentasCtrl', ['$scope', 'jsReportSrvc', 'ventaSrvc', function($scope, jsReportSrvc, ventaSrvc){

        $scope.params = { mes: (moment().month() + 1).toString(), anio: moment().year(), cliente: undefined, fdel: undefined, fal: undefined };
        $scope.clientes = [];

        ventaSrvc.lstClientes().then(function(d){ $scope.clientes = d; });

        $scope.geIvaVentas = function(){
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
            $scope.params.fdelstr = $scope.params.fdel != null && $scope.params.fdel != undefined && moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = $scope.params.fal != null && $scope.params.fal != undefined && moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';

            var test = false;
            jsReportSrvc.getPDFReport(test ? '' : 'HJilbOKoZ', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());