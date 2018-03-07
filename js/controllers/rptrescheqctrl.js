(function(){

    var rptrescheqctrl = angular.module('cpm.rptrescheqctrl', []);

    rptrescheqctrl.controller('rptResumenChequesCtrl', ['$scope', 'jsReportSrvc', 'monedaSrvc', function($scope, jsReportSrvc, monedaSrvc){

        $scope.params = { fdel: moment().toDate(), fal: moment().toDate(), idmoneda: '1', orden: '1' };
        $scope.monedas = [];

        monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });

        var test = false;
        $scope.getRptResumenCheques = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.idmoneda = $scope.params.idmoneda != null && $scope.params.idmoneda != undefined ? $scope.params.idmoneda : '1';
            $scope.params.orden = $scope.params.orden != null && $scope.params.orden != undefined ? $scope.params.orden : '1';
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? '' : 'SkB7oGMDW', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = { fdel: moment().toDate(), fal: moment().toDate(), idmoneda: '1', orden: '1' }; };

    }]);
}());