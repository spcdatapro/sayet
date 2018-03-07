(function(){

    var rptecuentaprovctrl = angular.module('cpm.rptecuentaprovctrl', []);

    rptecuentaprovctrl.controller('rptEcuentaProveedoresCtrl', ['$scope', 'rptEcuentaProveedoresSrvc', 'authSrvc', 'jsReportSrvc', '$sce','proveedorSrvc', function($scope, rptEcuentaProveedoresSrvc, authSrvc, jsReportSrvc, $sce,proveedorSrvc){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0,detalle: 0, prov: {id:0}};
        $scope.ecuentaproveedor = [];
        $scope.content = undefined;
        $scope.losProvs = [];

        $scope.getLstProveedores = function(){
            proveedorSrvc.lstProveedores().then(function(d){
                $scope.losProvs = d;
            });
        };

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function(){
            $scope.eccuentaproveedor = [];

        };

        $scope.getEcuentaProv = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.provstr = $scope.params.prov.id;

            jsReportSrvc.ecuentaProveedores($scope.params).then(function (result) {
                var file = new Blob([result.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                $scope.content = $sce.trustAsResourceUrl(fileURL);
            });

        };

        $scope.getEcuentaProvXLSX = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.provstr = $scope.params.prov.id;

            jsReportSrvc.ecuentaProveedoresXlsx($scope.params).then(function (result) {
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                saveAs(file, 'EcuentaProveedores.xlsx');
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Estado de Cuenta de Proveedores');
        };

        $scope.getLstProveedores();

    }]);

}());
