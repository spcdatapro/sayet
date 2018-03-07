(function(){

    var rptchqaprobctrl = angular.module('cpm.rptchqaprobctrl', []);

    rptchqaprobctrl.controller('rptChequesAprobacionCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', '$filter', '$confirm', 'bancoSrvc', 'monedaSrvc', '$window', function($scope, empresaSrvc, jsReportSrvc, authSrvc, $filter, $confirm, bancoSrvc, monedaSrvc, $window){

        $scope.params = {idempresa: undefined, fecha: moment().toDate(), bancos: '', bco: undefined, idmoneda: '1' };
        $scope.empresas = [];
        $scope.bancos = [];
        $scope.monedas = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });        
        monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });

        $scope.loadBancos = function(idempresa){
            if(idempresa == null || idempresa == undefined){ idempresa = 0; }
            bancoSrvc.lstBancosFltr(idempresa).then(function(d){ $scope.bancos = d; });
        };
        
        var test = false;
        $scope.getCheques = function(){ 
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
            $scope.params.idmoneda = $scope.params.idmoneda != null && $scope.params.idmoneda != undefined ? $scope.params.idmoneda : '1';
            
            var qstr = $scope.params.idempresa + '/' + $scope.params.fechastr + '/' + $scope.params.idmoneda + '/chequesb';
            $window.open('php/rptchequesaprob.php/gettxt/' + qstr);    
            /*
            jsReportSrvc.getReport(test ? '' : 'B1ICfUfDb', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'text/plain;charset=ANSI', endings:'transparent'});
                //var abreviatura = $filter('getById')($scope.empresas, $scope.params.idempresa).abreviatura;
                //abreviatura = abreviatura != null && abreviatura != undefined ? abreviatura : '';
                saveAs(file, 'chequesb.txt');
            });
            */
        };

        $scope.resetParams = function(){ $scope.params = {idempresa: undefined, fecha: moment().toDate(), bancos: '', bco: undefined, idmoneda: '1' }; $scope.loadBancos(0); };

        //$scope.loadBancos(0);

    }]);
}());