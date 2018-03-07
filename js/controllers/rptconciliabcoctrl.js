(function(){

    var rptconciliabcoctrl = angular.module('cpm.rptconciliabcoctrl', []);

    rptconciliabcoctrl.controller('rptConciliaBcoCtrl', ['$scope', 'rptConciliaBcoSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', 'bancoSrvc', function($scope, rptConciliaBcoSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce, bancoSrvc){

        $scope.params = {idbanco: undefined, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), saldobco: 0.00, objBanco: {}};
        $scope.bancos = [];
        $scope.conciliacion = {};
        $scope.content = null;

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                bancoSrvc.lstBancos($scope.params.idempresa).then(function(d){ $scope.bancos = d; });
            }
        });

        var test = false;
        $scope.getConciliacion = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.saldobco = $scope.params.saldobco != null && $scope.params.saldobco != undefined ? $scope.params.saldobco : 0.00;
            jsReportSrvc.getPDFReport(test ? 'SJCCFMc-b' : 'Bk-tqX5WW', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());

