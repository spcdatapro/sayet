(function(){

    var rptecuentaclictrl = angular.module('cpm.rptecuentaclictrl', []);

    rptecuentaclictrl.controller('rptEcuentaClientesCtrl', ['$scope', 'rptEcuentaClientesSrvc', 'authSrvc', 'jsReportSrvc', '$sce','clienteSrvc', 'empresaSrvc', function($scope, rptEcuentaClientesSrvc, authSrvc, jsReportSrvc, $sce,clienteSrvc, empresaSrvc){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0,detalle: 0, cliente: {id: 0}};
        $scope.ecuentacliente = [];
        $scope.content = undefined;
        $scope.clientes = [];
        $scope.empresas = [];
        $scope.objEmpresa = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        clienteSrvc.lstCliente().then(function(d){
            $scope.clientes = d;
        });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function(){
            $scope.ecuentacliente = [];
        };

        $scope.getEcuentaCli = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.clistr = $scope.params.cliente.id;
            $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;

            jsReportSrvc.ecuentaClientes($scope.params).then(function (result) {
                var file = new Blob([result.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                $scope.content = $sce.trustAsResourceUrl(fileURL);
            });

        };

        var test = false;

        $scope.getEcuentaCliXLSX = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.clistr = $scope.params.cliente.id;
            $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;

            jsReportSrvc.getReport(test ? 'HJMvjIxYz' : 'B1Q3xDgFf', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var nombre = moment($scope.params.al).format('DDMMYYYY');
                saveAs(file, 'EC_al_' + nombre + '.xlsx');
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Estado de Cuenta de Clientes');
        };

    }]);

}());
