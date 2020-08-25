(function(){

    var rptchqaprobctrl = angular.module('cpm.rptchqaprobctrl', []);

    rptchqaprobctrl.controller('rptChequesAprobacionCtrl', ['$scope', 'empresaSrvc', 'bancoSrvc', 'monedaSrvc', '$window', function($scope, empresaSrvc, bancoSrvc, monedaSrvc, $window){

        $scope.params = { idempresa: undefined, fecha: moment().toDate(), banco: undefined, idmoneda: '1' };
        $scope.empresas = [];
        $scope.bancos = [];
        $scope.monedas = [];

        empresaSrvc.lstEmpresas().then((d) => $scope.empresas = d);
        monedaSrvc.lstMonedas().then((d) => $scope.monedas = d);
        $scope.loadBancos = (idempresa) => bancoSrvc.lstBancosActivos(idempresa).then((d) => $scope.bancos = d);
        
        $scope.getCheques = () => { 
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
            $scope.params.idmoneda = $scope.params.idmoneda != null && $scope.params.idmoneda != undefined ? $scope.params.idmoneda : '1';
            var qstr = `${$scope.params.idempresa}/${$scope.params.fechastr}/${$scope.params.idmoneda}/chequesb/${$scope.params.banco}`;
            $window.open('php/rptchequesaprob.php/gettxt/' + qstr);
        };

        $scope.resetParams = () => { 
            $scope.params = {idempresa: undefined, fecha: moment().toDate(), banco: undefined, idmoneda: '1' };
            $scope.bancos = [];            
        };
    }]);
}());