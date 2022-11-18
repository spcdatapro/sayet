(function(){

    var rptdetcontventasctrl = angular.module('cpm.rptdetcontventasctrl', []);

    rptdetcontventasctrl.controller('rptDetContVentas', ['$scope', 'authSrvc', 'empresaSrvc', 'jsReportSrvc', 'clienteSrvc', function($scope, authSrvc, empresaSrvc, jsReportSrvc, clienteSrvc){

        $scope.params = { idempresa: undefined, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), tipo: '1' };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.objEmpresa = {};
        $scope.clientes = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.params.idempresa = $scope.objEmpresa.id.toString();
                });
            }
        });

        clienteSrvc.lstCliente().then(function(d){ $scope.clientes = d; });

        var test = false;
        $scope.getDetcontVentas = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD')
            $scope.params.tipo = !!$scope.params.tipo ? $scope.params.tipo : '';
            $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente != undefined ? $scope.params.idcliente : 0;
            jsReportSrvc.getPDFReport(test ? 'HkXEMYVFM' : 'rk_mkqNKG', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());

