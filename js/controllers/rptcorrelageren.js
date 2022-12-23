(function(){

    var rptcorrchctrl = angular.module('cpm.rptcorrelageren', []);

    rptcorrchctrl.controller('rptCorrelaGeren', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', 'tipoMovTranBanSrvc', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc, tipoMovTranBanSrvc){

        $scope.losBancos = [];
        $scope.params = { idempresa: undefined, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), fdelstr: '', falstr:'' };
        $scope.objBanco = undefined;
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.tipos = [];
        $scope.empresas = [];

        tipoMovTranBanSrvc.lstTiposMovGT().then(function(d){ $scope.tipos = d; });

        empresaSrvc.lstEmpresas().then(function(d) { $scope.empresas = d; });

        var test = false;
        $scope.getCorrelativosCheques = function(){
            $scope.params.idempresa = $scope.params.empresa != null && $scope.params.empresa !== undefined ? $scope.params.empresa : 0;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'SyuL_N5bZ' : 'HJVdFHq_i', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getCorrelativosChequesExcel = function(){
            $scope.params.idbanco = $scope.objBanco.id;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo !== undefined ? $scope.params.tipo : '';
            $scope.params.beneficiario = $scope.params.beneficiario != null && $scope.params.beneficiario !== undefined ? $scope.params.beneficiario : '';

            jsReportSrvc.getReport(test ? 'ryLu2BlYf' : 'rJ6ttH9us', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var nombre = $scope.objBanco.siglas.replace('/', '-') + '_' + ($scope.params.tipo !== '' ? ($scope.params.tipo + '_') : '') + moment($scope.params.fDel).format('DDMMYYYY') + '_' + moment($scope.params.fAl).format('DDMMYYYY');
                saveAs(file, 'CD_' + nombre + '.xlsx');
            });
        };

    }]);

}());
