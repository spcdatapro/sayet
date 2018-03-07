(function(){

    var formsretventactrl = angular.module('cpm.formsretventactrl', []);

    formsretventactrl.controller('formsRetVentaCtrl', ['$scope', '$filter', 'ventaSrvc', 'authSrvc', 'toaster', '$uibModal', '$confirm', 'empresaSrvc', function($scope, $filter, ventaSrvc, authSrvc, toaster, $uibModal, $confirm, empresaSrvc){

        $scope.idempresa = 0;
        $scope.ventas = [];
        $scope.empresas = [];
        $scope.params = {
            fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), fdelstr: '', falstr: '', idempresa: '0', numero: ''
        };
        
        authSrvc.getSession().then(function(usrLogged){
            $scope.params.idempresa = usrLogged.workingon.toString();
        });

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.loadFacturasConRet = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
            $scope.params.numero = $scope.params.numero != null && $scope.params.numero != undefined ? $scope.params.numero : '';
            ventaSrvc.editRow($scope.params, 'lstfactret').then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].fecpagoformisr = moment(d[i].fecpagoformisr).isValid() ? moment(d[i].fecpagoformisr).toDate() : null;
                    d[i].fechapagoformiva = moment(d[i].fechapagoformiva).isValid() ? moment(d[i].fechapagoformiva).toDate() : null;
                }
                $scope.ventas = d;
            });
        };
        
        //modal para el isr
        $scope.modalISR = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalISR.html',
                controller: 'ModalISRv',
                resolve:{
                    venta: function(){
                        return obj;
                    }
                }
            });
            modalInstance.result.then(function(){
                $scope.loadFacturasConRet();
            }, function(){ return 0; });
        };
        
        //modal para el iva
        $scope.modalIVA = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalIVA.html',
                controller: 'ModalIVAv',
                resolve:{
                    venta: function(){
                        return obj;
                    }
                }
            });
            modalInstance.result.then(function(){
                $scope.loadFacturasConRet();
            }, function(){ return 0; });
        };        

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    formsretventactrl.controller('ModalISRv', ['$scope', '$uibModalInstance', 'venta', 'ventaSrvc', function($scope, $uibModalInstance, venta, ventaSrvc){
        $scope.venta = venta;
        $scope.venta.isrlocal = parseFloat(($scope.venta.isr * $scope.venta.tipocambio).toFixed(2));
        //console.log($scope.venta);

        $scope.setMesAnio = function(){
            if(moment($scope.venta.fecpagoformisr).isValid()){
                $scope.venta.mesisr = moment($scope.venta.fecpagoformisr).month() + 1;
                $scope.venta.anioisr = moment($scope.venta.fecpagoformisr).year();

            }
        };

        $scope.ok = function () {
            $scope.venta.noformisr = $scope.venta.noformisr != null && $scope.venta.noformisr != undefined ? $scope.venta.noformisr : '';
            $scope.venta.noaccisr = $scope.venta.noaccisr != null && $scope.venta.noaccisr != undefined ? $scope.venta.noaccisr : '';
            $scope.venta.fecpagoformisrstr = moment($scope.venta.fecpagoformisr).isValid() ? moment($scope.venta.fecpagoformisr).format('YYYY-MM-DD') : '';
            $scope.venta.mesisr = $scope.venta.mesisr != null && $scope.venta.mesisr != undefined ? $scope.venta.mesisr : 0;
            $scope.venta.anioisr = $scope.venta.anioisr != null && $scope.venta.anioisr != undefined ? $scope.venta.anioisr : 0;
            ventaSrvc.editRow($scope.venta, 'uisr').then(function(){ $uibModalInstance.close(); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    //Controlador de formulario de retencion iva
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    formsretventactrl.controller('ModalIVAv', ['$scope', '$uibModalInstance', 'venta', 'ventaSrvc', function($scope, $uibModalInstance, venta, ventaSrvc){
        $scope.venta = venta;
        $scope.setMesAnio = function(){
            if(moment($scope.venta.fecpagoformiva).isValid()){
                $scope.venta.mespagoiva = moment($scope.venta.fecpagoformiva).month() + 1;
                $scope.venta.aniopagoiva = moment($scope.venta.fecpagoformiva).year();
            }
        };
        $scope.ok = function () {
            $scope.venta.noformiva = $scope.venta.noformiva != null && $scope.venta.noformiva != undefined ? $scope.venta.noformiva : '';
            $scope.venta.noacciva = $scope.venta.noacciva != null && $scope.venta.noacciva != undefined ? $scope.venta.noacciva : '';
            $scope.venta.fecpagoformivastr = moment($scope.venta.fecpagoformiva).isValid() ? moment($scope.venta.fecpagoformiva).format('YYYY-MM-DD') : '';
            $scope.venta.mespagoiva = $scope.venta.mespagoiva != null && $scope.venta.mespagoiva != undefined ? $scope.venta.mespagoiva : 0;
            $scope.venta.aniopagoiva = $scope.venta.aniopagoiva != null && $scope.venta.aniopagoiva != undefined ? $scope.venta.aniopagoiva : 0;
            ventaSrvc.editRow($scope.venta, 'uiva').then(function(){ $uibModalInstance.close(); });
            //console.log($scope.venta);
        };
        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
}());

