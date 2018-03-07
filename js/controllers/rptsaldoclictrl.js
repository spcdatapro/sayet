(function(){

    var rptsaldoclictrl = angular.module('cpm.rptsaldoclictrl', []);

    rptsaldoclictrl.controller('rptSaldoClientesCtrl', ['$scope', 'rptSaldoClientesSrvc', 'authSrvc', 'jsReportSrvc', '$sce','empresaSrvc', function($scope, rptSaldoClientesSrvc, authSrvc, jsReportSrvc, $sce, empresaSrvc){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0};
        $scope.saldocliente = [];
        $scope.content = undefined;
        $scope.empresas = [];
        $scope.objEmpresa = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function(){
            $scope.saldocliente = [];
        };

        $scope.getSalCli = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;

            jsReportSrvc.saldoClientes($scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                $scope.content = $sce.trustAsResourceUrl(fileURL);
            });
        };

        $scope.getSalCliXLSX = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');

            jsReportSrvc.saldoClientesXlsx($scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                saveAs(file, 'SaldoClientes.xlsx');
            });
        };
		
		 $scope.getFactsEmitidas = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');

            jsReportSrvc.factsemitidas($scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                $scope.content = $sce.trustAsResourceUrl(fileURL);
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Saldo de CLientes');
        };

    }]);

}());

