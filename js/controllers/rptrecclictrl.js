(function(){

    var rptrecclictrl = angular.module('cpm.rptrecclictrl', []);

    rptrecclictrl.controller('rptRecibosClienteCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'monedaSrvc', function($scope, jsReportSrvc, empresaSrvc, monedaSrvc){

        $scope.params = {
            fdel: moment().startOf('month').toDate(), fal:moment().endOf('month').toDate(), serie: undefined, numdel: undefined, numal: undefined, idempresa: undefined, lasEmpresas: undefined,
            idmoneda: undefined, encorrelativo: 0
        };

        $scope.empresas = [];
        $scope.monedas = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });

        var test = false;

        $scope.getRptRecibosCliente = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.serie = $scope.params.serie != null && $scope.params.serie != undefined ? $scope.params.serie : '';
            $scope.params.numdel = $scope.params.numdel != null && $scope.params.numdel != undefined ? $scope.params.numdel : 0;
            $scope.params.numal = $scope.params.numal != null && $scope.params.numal != undefined ? $scope.params.numal : 0;
            $scope.params.idmoneda = $scope.params.idmoneda != null && $scope.params.idmoneda != undefined ? $scope.params.idmoneda : 0;

            if($scope.params.lasEmpresas){
                if($scope.params.lasEmpresas.length > 0){
                    $scope.params.idempresa = objectPropsToList($scope.params.lasEmpresas, 'id', ',');
                }else{ $scope.params.idempresa = ''; }
            }else{ $scope.params.idempresa = ''; }

            //console.log($scope.params); return;
            var rpttest = 'S1Kw5Q8Wf', rpt = 'S1Kw5Q8Wf';
            if(+$scope.params.encorrelativo === 1){
                rpttest = 'Sk_TXxlMQ';
                rpt = 'B1spoxxfm';
            }

            jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
