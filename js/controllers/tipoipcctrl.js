(function(){

    var tipoipcctrl = angular.module('cpm.tipoipcctrl', ['cpm.tipocomprasrvc']);

    tipoipcctrl.controller('tipoIpcCtrl', ['$scope', 'tipoIpcSrvc', '$confirm', function($scope, tipoIpcSrvc, $confirm){

        $scope.tipoipc = {};
        $scope.lsttipoipc = [];


        $scope.getLstTipoIpc = function(){ tipoIpcSrvc.lstTipoIpc().then(function(d){ $scope.lsttipoipc = d; }); };

        $scope.getTipoIpc = function(idtipoipc){
            tipoIpcSrvc.getTipoIpc(parseInt(idtipoipc)).then(function(d){
                $scope.tipoipc = d[0];
            });
        };

        $scope.resetTipoIpc = function(){ $scope.tipoipc = {}; };

        $scope.addTipoIpc = function(obj){
            tipoIpcSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstTipoIpc();
                $scope.getTipoIpc(d.lastid);
            });
        };

        $scope.updTipoIpc = function(obj){
            tipoIpcSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstTipoIpc();
                $scope.getTipoIpc(obj.id);
            });
        };

        $scope.delTipoIpc = function(obj){
            $confirm({text: "¿Esta seguro(a) de eliminar " + obj.descripcion + "?", title: 'Eliminar', ok: 'Sí', cancel: 'No'}).then(function(){
                tipoIpcSrvc.editRow({ id: obj.id }, 'd').then(function(){$scope.getLstTipoIpc(); $scope.resetTipoIpc(); });
            });
        };

        $scope.getLstTipoIpc();

    }]);

}());
