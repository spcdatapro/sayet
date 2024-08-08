(function(){

    var rptestadoctactrl = angular.module('cpm.rptestadoctactrl', []);

    rptestadoctactrl.controller('rptEstadoCtaCtrl', ['$scope', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc){

        $scope.bancos = [];
        $scope.empresas = [];
        $scope.params = { fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), resumen: 0 };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.cargando = false;

        authSrvc.getSession().then(function(usrLogged){
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
            $scope.params.idempresa = usrLogged.workingon.toString();
            $scope.getBancos(usrLogged.workingon);
        });

        $scope.getBancos = function (idempresa) {
            $scope.params.idbanco = undefined;
            bancoSrvc.lstBancos(idempresa).then(function(d) {
                $scope.bancos = d;                        
            });
        }

        var test = false;

        prepParams = () => {            
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            $scope.params.resumen = $scope.params.resumen != null && $scope.params.resumen != undefined ? $scope.params.resumen : 0;
        };

        $scope.getData = function(){
            $scope.cargando = true;
            prepParams();
            jsReportSrvc.getPDFReport(test ? 'rJAPqqWXZ' : 'SJB5nj-QW', $scope.params).then(function(pdf){ 
                $scope.content = pdf; 
                $scope.cargando = false;
            });

        };

        $scope.getDataExcel = () => {
            $scope.cargando = true;
            prepParams();
            jsReportSrvc.getReport(test ? 'B1LjWdaBL' : 'B1LjWdaBL', $scope.params).then((result) => {
                const file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                saveAs(file, 'Estado_de_Cuenta.xlsx');
                $scope.cargando = false;
            });
        }
    }]);

}());
