(function(){

    var rptctrlccctrl = angular.module('cpm.rptctrlccctrl', []);

    rptctrlccctrl.controller('rptControlCajaChicaCtrl', ['$scope', 'authSrvc', 'beneficiarioSrvc', 'jsReportSrvc', 'empresaSrvc', function($scope, authSrvc, beneficiarioSrvc, jsReportSrvc, empresaSrvc){

        $scope.beneficiarios = []; 
        $scope.empresas = [];
        $scope.params = {
            idbeneficiario:undefined, fdini: undefined, faini: undefined, fdfin: undefined, fafin:undefined, empresas:'', estatus:'0', lstEmpresas: undefined, solocc: 0, idrembolso: undefined, solorem: 0
        };
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        beneficiarioSrvc.lstBeneficiarios().then(function(d){ $scope.beneficiarios = d; });
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        var test = false;
        $scope.getData = function(){                      
            $scope.params.fdinistr = $scope.params.fdini != null && $scope.params.fdini !== undefined ? moment($scope.params.fdini).format('YYYY-MM-DD') : '';
            $scope.params.fainistr = $scope.params.faini != null && $scope.params.faini !== undefined ? moment($scope.params.faini).format('YYYY-MM-DD') : '';
            $scope.params.fdfinstr = $scope.params.fdfin != null && $scope.params.fdfin !== undefined ? moment($scope.params.fdfin).format('YYYY-MM-DD') : '';
            $scope.params.fafinstr = $scope.params.fafin != null && $scope.params.fafin !== undefined ? moment($scope.params.fafin).format('YYYY-MM-DD') : '';
            $scope.params.empresas = $scope.params.lstEmpresas != null && $scope.params.lstEmpresas !== undefined ? objectPropsToList($scope.params.lstEmpresas, 'id', ',') : '';
            $scope.params.solocc = $scope.params.solocc != null && $scope.params.solocc !== undefined ? $scope.params.solocc : 0;
            $scope.params.solorem = $scope.params.solorem != null && $scope.params.solorem !== undefined ? $scope.params.solorem : 0;
            $scope.params.idreembolso = $scope.params.idreembolso != null && $scope.params.idreembolso !== undefined ? $scope.params.idreembolso : '';
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? '' : 'SJSP7jdD-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetData = function(){
            $scope.params = {
                idbeneficiario:undefined, fdini: undefined, faini: undefined, fdfin: undefined, fafin:undefined, empresas:'', estatus:'0', lstEmpresas: undefined, solocc: 1, solorem: 0
            };
        };

    }]);

}());
