(function () {

    var rptecuentaclictrl = angular.module('cpm.rptecuentaclictrl', []);

    rptecuentaclictrl.controller('rptEcuentaClientesCtrl', ['$scope', 'authSrvc', 'jsReportSrvc', '$sce', 'clienteSrvc', 'empresaSrvc', 'toaster', function ($scope, authSrvc, jsReportSrvc, $sce, clienteSrvc, empresaSrvc, toaster) {

        $scope.params = { del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, detalle: 0, cliente: { id: 0 } };
        $scope.ecuentacliente = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.clientes = [];
        $scope.empresas = [];
        $scope.objEmpresa = [];

        clienteSrvc.lstCliente().then(function (d) {
            $scope.clientes = d;
        });

        authSrvc.getSession().then(function (usrLogged) {
            if (parseInt(usrLogged.workingon) > 0) {
                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });
                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    });
                });
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function () {
            $scope.ecuentacliente = [];
        };

        $scope.getEcuentaCli = function () {
            // console.log($scope.params);
            if (+$scope.params.cliente.id > 0) {
                $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
                $scope.params.clistr = $scope.params.cliente.id;
                $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;

                jsReportSrvc.ecuentaClientes($scope.params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/pdf' });
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
                });
            } else {
                toaster.pop({ type: 'error', title: 'Cliente', body: 'Por favor seleccione un cliente.', timeout: 7000 });
            }
        };

        var test = false;

        $scope.getEcuentaCliXLSX = function () {
            // console.log($scope.params);
            if (+$scope.params.cliente.id > 0) {
                $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
                $scope.params.clistr = $scope.params.cliente.id;
                $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;

                jsReportSrvc.getReport(test ? 'HJMvjIxYz' : 'B1Q3xDgFf', $scope.params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    var nombre = moment($scope.params.al).format('DDMMYYYY');
                    saveAs(file, 'EC_al_' + nombre + '.xlsx');
                });
            } else {
                toaster.pop({ type: 'error', title: 'Cliente', body: 'Por favor seleccione un cliente.', timeout: 7000 });
            }
        };

        $scope.printVersion = function () {
            PrintElem('#toPrint', 'Estado de Cuenta de Clientes');
        };

    }]);

}());
