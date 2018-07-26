(function(){

    var rptbalsalctrl = angular.module('cpm.rptbalsalctrl', []);

    rptbalsalctrl.controller('rptBalanceSaldosCtrl', ['$scope', 'rptBalanceSaldosSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', function($scope, rptBalanceSaldosSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce){

        $scope.params = {
            del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, solomov: 1, nivel: '7', nofolio: undefined, noheader: 0
        };
        $scope.balanceSaldos = [];
        $scope.content = undefined;
        $scope.empresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        var test = false;
        $scope.getBalSal = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.solomov = $scope.params.solomov != null && $scope.params.solomov !== undefined ? $scope.params.solomov : 0;
            $scope.params.nofolio = $scope.params.nofolio != null && $scope.params.nofolio !== undefined ? $scope.params.nofolio : '';
            $scope.params.noheader = $scope.params.noheader != null && $scope.params.noheader !== undefined ? $scope.params.noheader : 0;
            jsReportSrvc.getPDFReport(test ? 'SkU16RU7W' : 'SkU16RU7W', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getBalSalXLSX = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.solomov = $scope.params.solomov != null && $scope.params.solomov !== undefined ? $scope.params.solomov : 0;
            $scope.params.nofolio = $scope.params.nofolio != null && $scope.params.nofolio !== undefined ? $scope.params.nofolio : '';
            $scope.params.noheader = $scope.params.noheader != null && $scope.params.noheader !== undefined ? $scope.params.noheader : 0;

            jsReportSrvc.getReport(test ? 'BJT2GuhOM' : 'rkQ3r_huG', $scope.params).then(function(result){
                //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                saveAs(file, 'BS_' + nombre + '.xlsx');
            });
        };
    }]);

}());
