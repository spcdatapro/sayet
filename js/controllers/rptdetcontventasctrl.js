(function(){

    var rptdetcontventasctrl = angular.module('cpm.rptdetcontventasctrl', []);

    rptdetcontventasctrl.controller('rptDetContVentas', ['$scope', 'authSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, authSrvc, empresaSrvc, jsReportSrvc){

        $scope.params = { idempresa: undefined, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), tipo: '1' };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.objEmpresa = {};

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.params.idempresa = $scope.objEmpresa.id.toString();
                });
            }
        });

        var test = false;
        $scope.getDetcontVentas = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD')
            $scope.params.tipo = !!$scope.params.tipo ? $scope.params.tipo : '';
            jsReportSrvc.getPDFReport(test ? 'HkXEMYVFM' : 'rk_mkqNKG', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());

