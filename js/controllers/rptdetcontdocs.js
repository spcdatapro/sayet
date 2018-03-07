(function(){

    var rptdetcontdocsctrl = angular.module('cpm.rptdetcontdocsctrl', []);

    rptdetcontdocsctrl.controller('rptDetContDocsCtrl', ['$scope', 'detContSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'tipoMovTranBanSrvc', 'jsReportSrvc', function($scope, detContSrvc, authSrvc, bancoSrvc, empresaSrvc, tipoMovTranBanSrvc, jsReportSrvc){

        $scope.objEmpresa = {};
        $scope.losBancos = [];
        $scope.tipotrans = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idbanco: 0, abreviatura: '', fdelstr: '', falstr:'' };
        $scope.losDocs = [];
        $scope.objBanco = undefined;
        $scope.objTipotrans = {};
        $scope.data = [];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.params.idempresa = parseInt($scope.objEmpresa.id);
                    bancoSrvc.lstBancos(parseInt($scope.objEmpresa.id)).then(function(d) {
                        $scope.losBancos = d;
                    });
                });
            }
        });

        tipoMovTranBanSrvc.lstTiposMovTB().then(function(d){ $scope.tipotrans = d; });

        var test = false;
        $scope.getDetContDocs = function(){
            $scope.params.idbanco = $scope.objBanco.id;
            $scope.params.abreviatura = $scope.objTipotrans.abreviatura !== null && $scope.objTipotrans.abreviatura !== undefined ? $scope.objTipotrans.abreviatura : '';
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'rJ-Vr_5-b' : 'ByNjEKcZ-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());
