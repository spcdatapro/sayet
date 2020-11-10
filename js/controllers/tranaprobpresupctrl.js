(function(){

    const tranaprobpresupctrl = angular.module('cpm.tranaprobpresupctrl', []);

    tranaprobpresupctrl.controller('tranAprobPresupuestoCtrl', ['$scope', 'presupuestoSrvc', '$confirm', '$filter', 'authSrvc', 'DTOptionsBuilder', '$uibModal', 'toaster', ($scope, presupuestoSrvc, $confirm, $filter, authSrvc, DTOptionsBuilder, $uibModal, toaster) => {

        $scope.presupuestos = [];
        $scope.usrdata = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withBootstrap().withOption('paging', false).withOption('order', false);

        authSrvc.getSession().then((usrLogged) => {
            $scope.usrdata = usrLogged;
            $scope.getPendientes();
        });

        procData = (data) => {
            data.map((d) => {
                d.id = +d.id;
                d.aprobada = +d.aprobada;
            });
            return data;
        };

        $scope.getPendientes = () => presupuestoSrvc.presupuestosPendientes($scope.usrdata.uid).then((d) => { $scope.presupuestos = procData(d); });

        $scope.verDetPresup = (obj) => {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalDetallePresupuesto.html',
                controller: 'ModalDetPresupCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    presupuesto: () => obj
                }
            });

            modalInstance.result.then(() => { }, () => { });
        };

        $scope.verAdjuntos = (obj) => {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAdjuntosPresupuesto.html',
                controller: 'ModalAdjuntoPresupCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    presupuesto: () => obj
                }
            });

            modalInstance.result.then(() => { }, () => { });            
        };

        $scope.aprobar = (obj) => {
            if(+obj.aprobada === 1){
                $confirm({text: '¿Esta seguro(a) de aprobar el presupuesto No. ' + obj.numero +'?', title: 'Aprobar presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                    obj.idusuario = $scope.usrdata.uid;
                    presupuestoSrvc.editRow(obj, 'ap').then(function(){
                        $scope.getPendientes();
                        toaster.pop('info', 'Presupuesto aprobado', 'Se aprobó el presupuesto No. ' + obj.numero, 'timeout:1500');
                    });
                }, function(){
                    obj.aprobada = 0;
                });
            }
        };

        $scope.denegar = (obj) => {
            if(+obj.denegada === 1){
                $confirm({text: '¿Esta seguro(a) de denegar el presupuesto No. ' + obj.numero +'?', title: 'Denegar presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                    obj.idusuario = $scope.usrdata.uid;
                    presupuestoSrvc.editRow(obj, 'np').then(function(){
                        $scope.getPendientes();
                        toaster.pop('info', 'Presupuesto denegado', 'Se denegó el presupuesto No. ' + obj.numero, 'timeout:1500');
                    });
                }, function(){
                    obj.denegada = 0;
                });
            }
        };
    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    tranaprobpresupctrl.controller('ModalAdjuntoPresupCtrl', ['$scope', '$uibModalInstance', 'toaster', 'presupuesto', 'presupuestoSrvc', '$uibModal', function($scope, $uibModalInstance, toaster, presupuesto, presupuestoSrvc, $uibModal){
        $scope.presupuesto = presupuesto;
        $scope.lstadjuntos = [];

        $scope.loadAdjuntos = () => presupuestoSrvc.lstOtsAdjuntos($scope.presupuesto.idot).then((d) => $scope.lstadjuntos = d);

        $scope.cancel = () =>$uibModalInstance.dismiss('cancel');

        $scope.loadAdjuntos();
    }]);

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    tranaprobpresupctrl.controller('ModalDetPresupCtrl', ['$scope', '$uibModalInstance', 'toaster', 'presupuesto', 'presupuestoSrvc', '$uibModal', function($scope, $uibModalInstance, toaster, presupuesto, presupuestoSrvc, $uibModal){
        $scope.presupuesto = presupuesto;
        $scope.lstdetpagos = [];

        procDataDet = (data) => {
            data.map((d) => {
                d.id = parseInt(d.id);
                d.iddetpresup = parseInt(d.iddetpresup);
                d.nopago = parseInt(d.nopago);
                d.porcentaje = parseFloat(parseFloat(d.porcentaje).toFixed(4));
                d.monto = parseFloat(parseFloat(d.monto).toFixed(2));
            });
            return data;
        };

        $scope.loadData = () => presupuestoSrvc.lstDetPagoOt($scope.presupuesto.idot).then((d) => $scope.lstdetpagos = procDataDet(d));

        $scope.cancel = () =>$uibModalInstance.dismiss('cancel');

        $scope.loadData();
    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    tranaprobpresupctrl.controller('ModalDetPagosOtCtrlAprob', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', function($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot){
        $scope.ot = ot;
        $scope.lstdetpagos = [];
        $scope.fpago = { iddetpresup: ot.id };
        $scope.sumporcentaje = 0.0000;
        $scope.sumvalor = 0.00;

        function procDataDet(d){
            $scope.sumporcentaje = 0.0000;
            $scope.sumvalor = 0.00;
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].iddetpresup = parseInt(d[i].iddetpresup);
                d[i].nopago = parseInt(d[i].nopago);
                d[i].porcentaje = parseFloat(parseFloat(d[i].porcentaje).toFixed(4));
                $scope.sumporcentaje += d[i].porcentaje;
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                $scope.sumvalor += d[i].monto;
            }

            $scope.fpago.porcentaje = d.length > 0 ? (100 - $scope.sumporcentaje) : 100;
            $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));

            return d;
        }

        $scope.loadData = function(){
            presupuestoSrvc.lstDetPagoOt($scope.ot.id).then(function(d){ $scope.lstdetpagos = procDataDet(d); });
        };

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.loadData();

        $scope.calcValor = function(){
            var tmpVal = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
            if( ($scope.sumvalor + tmpVal) <= parseFloat($scope.ot.monto) ){
                $scope.fpago.monto = tmpVal;
            }else{
                toaster.pop('error', 'Error en el monto', 'La suma de las formas de pago no puede exceder al total de la OT', 'timeout:1500');
                $scope.loadData();
            }

        };

        $scope.calcPorcentaje = function(){
            var tmpPor = parseFloat(parseFloat(parseFloat($scope.fpago.monto) * 100.0000 / parseFloat($scope.ot.monto)).toFixed(4));
            if(($scope.sumporcentaje + tmpPor) <= 100){
                $scope.fpago.porcentaje = tmpPor;
            }else{
                toaster.pop('error', 'Error en el porcentaje', 'La suma porcentual no puede ser mayor a 100.00%', 'timeout:1500');
                $scope.loadData();
            }
        };

        $scope.resetFPago = function(){
            $scope.fpago = { iddetpresup: ot.id }
        };

        $scope.addFormaPago = function(obj){
            obj.notas = obj.notas != undefined && obj.notas != null ? obj.notas : '';
            presupuestoSrvc.editRow(obj, 'cdp').then(function(){
                $scope.loadData();
                $scope.resetFPago();
            });
        };

        $scope.delFormaPago = function(obj){
            $confirm({text: '¿Esta seguro(a) de eliminar la forma de pago No. ' + obj.nopago + '?', title: 'Eliminar forma de pago', ok: 'Sí', cancel: 'No'}).then(function() {
                presupuestoSrvc.editRow({id: obj.id}, 'ddp').then(function(){ $scope.loadData(); $scope.resetFPago(); });
            });
        };

    }]);

}());
