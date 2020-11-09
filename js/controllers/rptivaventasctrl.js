(function(){

    var rptivaventactrl = angular.module('cpm.rptivaventactrl', []);

    rptivaventactrl.controller('rptIVAVentasCtrl', ['$scope', 'jsReportSrvc', 'ventaSrvc', function($scope, jsReportSrvc, ventaSrvc){

        $scope.params = { mes: (moment().month() + 1).toString(), anio: moment().year(), cliente: undefined, fdel: undefined, fal: undefined, ordenalfa: 1 };
        $scope.clientes = [];
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        ventaSrvc.lstClientes().then(function(d){ $scope.clientes = d; });

        $scope.geIvaVentas = function(){
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
            $scope.params.fdelstr = $scope.params.fdel != null && $scope.params.fdel != undefined && moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = $scope.params.fal != null && $scope.params.fal != undefined && moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.ordenalfa = $scope.params.ordenalfa != null && $scope.params.ordenalfa !== undefined ? +$scope.params.ordenalfa : 0;
            var test = false;
            jsReportSrvc.getPDFReport(test ? '' : 'HJilbOKoZ', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.geIvaVentasExcel = function(){
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
            $scope.params.fdelstr = $scope.params.fdel != null && $scope.params.fdel != undefined && moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = $scope.params.fal != null && $scope.params.fal != undefined && moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.ordenalfa = $scope.params.ordenalfa != null && $scope.params.ordenalfa !== undefined ? +$scope.params.ordenalfa : 0;
            var test = false;
            jsReportSrvc.getReport(test ? '' : 'Sk_jEgDYv', $scope.params).then(function (result) {
            var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
            var nombre = $scope.meses[$scope.params.mes - 1] + '_' + $scope.params.anio;
            saveAs(file, 'PagoIVA_' + nombre + '.xlsx');
        });
        };


    }]);

}());