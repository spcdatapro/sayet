(function(){

    var rptbalgenctrl = angular.module('cpm.rptbalgenctrl', []);

    rptbalgenctrl.controller('rptBalanceGeneralCtrl', ['$scope', 'rptBalanceGeneralSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', function($scope, rptBalanceGeneralSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce){

        $scope.params = {al: moment().endOf('month').toDate(), idempresa: 0, acumulado: 1, nivel: '7', solomov: 1};
        $scope.balancegeneral = [];
        $scope.content = '';
        $scope.empresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        var test = false;
        $scope.getBalGen = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.acumulado = 1;
            $scope.params.solomov = $scope.params.solomov != null && $scope.params.solomov != undefined ? $scope.params.solomov : 0;
            jsReportSrvc.getPDFReport(test ? '' : 'H1HaXRUmb', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getBalGenXlsx = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.acumulado = 1;
            $scope.params.solomov = $scope.params.solomov != null && $scope.params.solomov != undefined ? $scope.params.solomov : 0;
            jsReportSrvc.getReport(test ? 'BJW_idhdz': 'BkpKK_2dz', $scope.params).then(function(result){
                //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.al).format('DDMMYYYY');
                saveAs(file, 'BG_' + nombre + '.xlsx');
            });
        };

    }]);

}());
