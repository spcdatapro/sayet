(function(){

    var plnpagoboletoornatoctrl = angular.module('cpm.plnpagoboletoornatoctrl', []);

    plnpagoboletoornatoctrl.controller('plnPagoBoletoOrnatoCtrl', ['$scope', 'plnPagoBoletoOrnatoSrvc', 'jsReportSrvc', '$window', 'toaster', 'empresaSrvc', function($scope, plnPagoBoletoOrnatoSrvc, jsReportSrvc, $window, toaster, empresaSrvc){

        $scope.params = { anio: moment().year(), idempresa: undefined };
        $scope.boletos = [];
        $scope.empresas = [];

        empresaSrvc.lstEmpresasPlanilla().then(function(d){ $scope.empresas = d; });

        $scope.getBoletos = function(){
            plnPagoBoletoOrnatoSrvc.lstPagosBoleto($scope.params.anio, $scope.params.idempresa).then(function(d){
                $scope.boletos = d;
            });
        };

        $scope.updPagado = function(idpago, pagado){
            plnPagoBoletoOrnatoSrvc.editRow({id: idpago, pagado: pagado}, 'u').then(function(){
                toaster.pop('info', 'Registro de pago', 'Se actualizó el pago del boleto de ornato.');
                $scope.getBoletos();
            });
        };

        $scope.printReporte = function(){
            $scope.params.idempresa = $scope.params.idempresa !== null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : 0;
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'SJLUdK1Im' : 'SJLUdK1Im', $scope.params).then(function(pdf){
                $window.open(pdf);
            });
        };

    }]);

}());