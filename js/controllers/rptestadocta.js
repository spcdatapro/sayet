(function(){

    var rptestadoctactrl = angular.module('cpm.rptestadoctactrl', []);

    rptestadoctactrl.controller('rptEstadoCtaCtrl', ['$scope', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc){

        $scope.objEmpresa = {};
        $scope.losBancos = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idbanco: 0, fdelstr: '', falstr:'', resumen: 0 };
        $scope.objBanco = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

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

        var test = false;

        prepParams = () => {
            $scope.params.idbanco = $scope.objBanco[0].id;            
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            $scope.params.resumen = $scope.params.resumen != null && $scope.params.resumen != undefined ? $scope.params.resumen : 0;
        };

        $scope.getData = function(){
            prepParams();
            jsReportSrvc.getPDFReport(test ? 'rJAPqqWXZ' : 'SJB5nj-QW', $scope.params).then(function(pdf){ $scope.content = pdf; });

        };

        $scope.getDataExcel = () => {
            prepParams();
            jsReportSrvc.getReport(test ? 'B1LjWdaBL' : 'B1LjWdaBL', $scope.params).then((result) => {
                const file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                saveAs(file, 'Estado_de_Cuenta.xlsx');
            });
        }
    }]);

}());
