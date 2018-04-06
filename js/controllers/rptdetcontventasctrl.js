(function(){

    var rptdetcontventasctrl = angular.module('cpm.rptdetcontventasctrl', []);

    rptdetcontventasctrl.controller('rptDetContVentas', ['$scope', 'authSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, authSrvc, empresaSrvc, jsReportSrvc){

        $scope.params = { idempresa: undefined, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate() };
        $scope.content = null;
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
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'HkXEMYVFM' : 'rk_mkqNKG', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        /*
        $scope.getCorrelativosChequesExcel = function(){
            $scope.params.idbanco = $scope.objBanco.id;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo != undefined ? $scope.params.tipo : '';

            jsReportSrvc.getReport(test ? 'ryLu2BlYf' : 'Skggz8xYM', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var nombre = $scope.objBanco.siglas.replace('/', '-') + '_' + ($scope.params.tipo != '' ? ($scope.params.tipo + '_') : '') + moment($scope.params.fDel).format('DDMMYYYY') + '_' + moment($scope.params.fAl).format('DDMMYYYY');
                saveAs(file, 'CD_' + nombre + '.xlsx');
            });
        };
        */
    }]);

}());

