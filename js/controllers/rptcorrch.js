(function(){

    var rptcorrchctrl = angular.module('cpm.rptcorrchctrl', []);

    rptcorrchctrl.controller('rptCorrChCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', 'tipoMovTranBanSrvc', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc, tipoMovTranBanSrvc){

        $scope.objEmpresa = {};
        $scope.losBancos = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idbanco: 0, fdelstr: '', falstr:'', tipo: undefined };
        $scope.objBanco = undefined;
        $scope.content = null;
        $scope.tipos = [];

        tipoMovTranBanSrvc.lstTiposMovTB().then(function(d){ $scope.tipos = d; });

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
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo != undefined ? $scope.params.tipo : '';
            jsReportSrvc.getPDFReport(test ? 'SyuL_N5bZ' : 'BJEkzB5W-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

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

    }]);

}());
