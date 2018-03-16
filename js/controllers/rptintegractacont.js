(function(){

    var rptintegractacontctrl = angular.module('cpm.rptintegractacontctrl', []);

    rptintegractacontctrl.controller('rptIntegraCuentaContableCtrl', ['$scope', 'empresaSrvc', 'cuentacSrvc', 'jsReportSrvc', 'authSrvc', function($scope, empresaSrvc, cuentacSrvc, jsReportSrvc, authSrvc){

        $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate(), idcuenta: undefined, idempresa: undefined};
        $scope.empresas = [];
        $scope.cuentas = [];
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = usrLogged.workingon.toString();
                $scope.loadCuentasContables();
            }
        });

        $scope.loadCuentasContables = function(){
            cuentacSrvc.getByTipo($scope.params.idempresa, 0).then(function(d){ $scope.cuentas = d; });
        };

        var test = false;
        $scope.getIntegracion = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'HyF5UkDFz' : 'B1RxL3FKG', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
