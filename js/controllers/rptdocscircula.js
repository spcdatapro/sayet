(function(){

    var rptdocscirculactrl = angular.module('cpm.rptdocscirculactrl', []);

    rptdocscirculactrl.controller('rptDocsCirculaCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc){

        $scope.objEmpresa = {};
        $scope.losBancos = [];
        $scope.params = { idempresa: 0, fAl: moment().toDate(), idbanco: 0, falstr:'' };
        $scope.content = '';

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
        $scope.getDocsCirculando = function(){
            //$scope.params.idbanco = $scope.objBanco[0] !== null && $scope.objBanco[0] !== undefined ? ($scope.objBanco.length == 1 ? $scope.objBanco[0].id : 0) : 0;
            //$scope.params.idbanco = $scope.objBanco.id;
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? '' : 'HyxTB2Oub', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };
       

    }]);

}());
