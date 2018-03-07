(function(){

    var rptcatbcoctrl = angular.module('cpm.rptcatbcoctrl', ['cpm.bancosrvc']);

    rptcatbcoctrl.controller('rptCatBcoCtrl', ['$scope', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'cuentacSrvc', '$confirm', 'monedaSrvc', function($scope, authSrvc, bancoSrvc, empresaSrvc, cuentacSrvc, $confirm, monedaSrvc){

        $scope.elBco = {correlativo: 1};
        $scope.losBancos = [];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.elBco.objEmpresa = r[0];
                    $scope.getLstBancos();
                });
            }
        });

        $scope.getLstBancos = function(){
            bancoSrvc.lstBancos($scope.elBco.objEmpresa.id).then(function(d){ $scope.losBancos = d; });
        };
        
        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Catálogo de bancos');
        };        
    }]);
}());
