(function(){

    var rptsaldoprovctrl = angular.module('cpm.rptsaldoprovctrl', []);

    rptsaldoprovctrl.controller('rptSaldoProveedoresCtrl', ['$scope', 'rptSaldoProveedoresSrvc', 'authSrvc', 'jsReportSrvc', '$sce', function($scope, rptSaldoPorveedoresSrvc, authSrvc, jsReportSrvc, $sce){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0};
        $scope.saldocliente = [];
        $scope.content = undefined;

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function(){
            $scope.saldoproveedor = [];
        };

        $scope.getSalProv = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');

            jsReportSrvc.saldoProveedores($scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                $scope.content = $sce.trustAsResourceUrl(fileURL);
            });
        };

        $scope.getSalProvXLSX = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');

            jsReportSrvc.saldoProveedoresXlsx($scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                saveAs(file, 'SaldoProveedores.xlsx');
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Saldo de Proveedores');
        };

    }]);

}());

