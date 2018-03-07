(function(){

    var rptfichaclientectrl = angular.module('cpm.rptfichaclientectrl', []);

    rptfichaclientectrl.controller('rptFichaClienteCtrl', ['$scope', 'clienteSrvc', 'jsReportSrvc', function($scope, clienteSrvc, jsReportSrvc){

        $scope.params = { idcliente: undefined };
        $scope.clientes = [];

        clienteSrvc.lstCliente().then(function(d){ $scope.clientes = d; });

        var test = false;
        $scope.getRptFichaCliente = function(){
            jsReportSrvc.getPDFReport(test ? 'HJfp-erkg' : 'SyZ8gmHkx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = { idcliente: undefined }; };

    }]);
}());
