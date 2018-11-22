(function(){

    var rptfichaclientectrl = angular.module('cpm.rptfichaclientectrl', []);

    rptfichaclientectrl.controller('rptFichaClienteCtrl', ['$scope', 'clienteSrvc', 'jsReportSrvc', '$filter', function($scope, clienteSrvc, jsReportSrvc, $filter){

        $scope.params = { idcliente: undefined };
        $scope.clientes = [];

        clienteSrvc.lstCliente().then(function(d){ $scope.clientes = d; });

        var test = false;
        $scope.getRptFichaCliente = function(){
            jsReportSrvc.getPDFReport(test ? 'HJfp-erkg' : 'SyZ8gmHkx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getRptFichaClienteExcel = function(){
            jsReportSrvc.getReport(test ? 'ryocwUEC7' : 'ryocwUEC7', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                saveAs(file, 'Ficha_Cliente_' + $filter('padNumber')(+$scope.params.idcliente, 5) + '.xlsx');
            });
        };

        $scope.resetParams = function(){ $scope.params = { idcliente: undefined }; };

    }]);
}());
