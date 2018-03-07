(function(){

    var rptantiprovctrl = angular.module('cpm.rptantiprovctrl', []);

    rptantiprovctrl.controller('rptAntiProveedoresCtrl', ['$scope', 'rptAntiProveedoresSrvc', 'authSrvc', 'jsReportSrvc', '$sce','proveedorSrvc', function($scope, rptAntiProveedoresSrvc, authSrvc, jsReportSrvc, $sce,proveedorSrvc){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0,detalle: 0, prov: {id:0}};
        $scope.antiproveedor = [];
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
            $scope.antiproveedor = [];
        };

        $scope.getAntiProv = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.provstr = $scope.params.prov.id;

            if($scope.params.detalle == 1){

                jsReportSrvc.antiProveedoresDet($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
                });
            }else {
                jsReportSrvc.antiProveedores($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
                });
            }
        };

        $scope.getAntiProvXLSX = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.provstr = $scope.params.prov.id;

            if($scope.params.detalle == 1){
                jsReportSrvc.antiProveedoresDetXlsx($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    saveAs(file, 'AntiProveedores.xlsx');
                });
            }else {
                jsReportSrvc.antiProveedoresXlsx($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    saveAs(file, 'AntiProveedores.xlsx');
                });
            }
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Antiguedad de Proveedores');
        };

        $scope.getLstProveedores();

    }]);

}());

