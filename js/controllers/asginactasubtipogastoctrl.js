(function(){

    var asignactasubtipogastoctrl = angular.module('cpm.asignactasubtipogastoctrl', []);

    asignactasubtipogastoctrl.controller('asignaCtaSubTipoGastoCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'cuentacSrvc', '$confirm', '$filter', 'tipogastoSrvc', '$uibModal', function($scope, authSrvc, empresaSrvc, cuentacSrvc, $confirm, $filter, tipogastoSrvc, $uibModal){
        $scope.subtiposgasto = [];

        tipogastoSrvc.lstSubTipoGasto().then(function(d){
            $scope.subtiposgasto = d;
        });

        $scope.asignaCuenta = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAsignaCta.html',
                controller: 'ModalAsignaCtaCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    subtipogasto: function(){ return obj; }
                }
            });

            modalInstance.result.then(function(){
                console.log('Modal cerrada')
            }, function(){ return 0; });
        };
        
    }]);

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    asignactasubtipogastoctrl.controller('ModalAsignaCtaCtrl', ['$scope', '$uibModalInstance', 'toaster', 'subtipogasto', 'cuentacSrvc', 'empresaSrvc', 'tipogastoSrvc', '$confirm', function($scope, $uibModalInstance, toaster, subtipogasto, cuentacSrvc, empresaSrvc, tipogastoSrvc, $confirm){
        $scope.subtipogasto = subtipogasto;
        $scope.empresas = [];
        $scope.cuentas = [];
        $scope.detsubtipo = {idsubtipogasto: subtipogasto.id, idempresa:undefined, idcuentac:undefined};

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        
        //$scope.ok = function () { $uibModalInstance.close($scope.fcierre); };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };      

        $scope.loadDetalle = function(){
            tipogastoSrvc.getDetSubTipo($scope.subtipogasto.id).then(function(d){
                $scope.detalles = d;
            });
        };

        $scope.getCuentas = function(item, model){            
            $scope.detsubtipo.idcuentac = undefined;
            cuentacSrvc.getByTipo(item.id, 0).then(function(d){
                $scope.cuentas = d;
            });
        };

        $scope.asginaCta = function(obj){
            //console.log(obj);
            tipogastoSrvc.editRow(obj, 'acc').then(function(){
                $scope.loadDetalle();
            });
        };

        $scope.delDetSubtipo = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta asignación?', title: 'Eliminar asignación', ok: 'Sí', cancel: 'No'}).then(function() {
                tipogastoSrvc.editRow({ id:obj.id }, 'dcc').then(function(){ $scope.loadDetalle(); });
            });
        };

        $scope.loadDetalle();

    }]);

}());