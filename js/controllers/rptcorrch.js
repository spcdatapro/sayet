(function(){

    var rptcorrchctrl = angular.module('cpm.rptcorrchctrl', []);

    rptcorrchctrl.controller('rptCorrChCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc){

        $scope.objEmpresa = {};
        $scope.losBancos = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idbanco: 0, fdelstr: '', falstr:'' };
        $scope.objBanco = undefined;
        $scope.content = null;

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.params.idempresa = parseInt($scope.objEmpresa.id);
                    bancoSrvc.lstBancos($scope.params.idempresa).then(function(d) {
                        $scope.losBancos = d;
                    });
                });
            }
        });

        var test = false;
        $scope.getCorrelativosCheques = function(){
            $scope.params.idbanco = $scope.objBanco.id;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'SyuL_N5bZ' : 'BJEkzB5W-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());
