(function () {

    var controller = angular.module('cpm.cargosplnctrl', []);

    controller.controller('cargosPlnCtrl', ['$scope', 'planillaSrvc', '$uibModal', 'toaster', 'authSrvc', ($scope, planillaSrvc, $uibModal, toaster, authSrvc) => {

        $scope.idusuario = undefined;
        $scope.cargos = [];
        $scope.generando = false;
        $scope.generados = [];

        authSrvc.getSession().then((usuario) => { $scope.idusuario = usuario.uid; });

        function getPendientes() {
            planillaSrvc.getPendientes().then((d) => {
                d.forEach(data => {
                    data.total = (+data.finiquito + +data.vacaciones + +data.aguinaldo + +data.bono + +data.ordinario + +data.extra + +data.otrosbono)
                        - (+data.prestamos + +data.anticipos + +data.otrosdesc);
                });
                $scope.cargos = d;
            });
        }

        $scope.genTran = (data) => {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalTranban.html',
                controller: 'ModalTranban',
                windowClass: 'app-modal-window',
                resolve: {
                    data: data
                }
            });

            modalInstance.result.then(function (obj) {
                $scope.generando = true;
                obj.idusuario = $scope.idusuario;
                planillaSrvc.genera(obj).then((d) => {
                    getPendientes();
                    $scope.generando = false;
                    toaster.pop({ type: d.tipo, title: 'Generación de transacción', body: d.mensaje, timeout: 10000 });
                });
            });
        };

        getPendientes();

    }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalTranban', ['$scope', '$uibModalInstance', 'data', 'bancoSrvc', 'tipoMovTranBanSrvc', 'cuentacSrvc', function
        ($scope, $uibModalInstance, data, bancoSrvc, tipoMovTranBanSrvc, cuentacSrvc) {

        $scope.bancos = [];
        $scope.tiposmov = [];
        $scope.cuentas = [];
        $scope.data = data;
        $scope.tran = { fecha: moment().toDate(), concepto: data.concepto };

        bancoSrvc.lstBancos(data.idempresa).then((d) => { $scope.bancos = d; });
        tipoMovTranBanSrvc.getBySuma(0).then(function (d) { $scope.tiposmov = d; });
        cuentacSrvc.getByTipo(data.idempresa, 0).then(function (d) { $scope.cuentas = d; });

        $scope.getNumCheque = function (tran) {
            if (tran.idbanco > 0) {
                if (tran.idtipo == 1) {
                    bancoSrvc.getCorrelativoBco(tran.idbanco).then(function (num) { $scope.tran.numero = +num[0].correlativo });
                } else {
                    $scope.tran.numero = undefined;
                }
            }
        };

        $scope.ok = function (tran) {
            obj = data;
            obj.fechatran = moment(tran.fecha).format('YYYY-MM-DD');
            obj.idbanco = tran.idbanco;
            obj.tipo = tran.idtipo == 1 ? 'C' : 'B';
            obj.numero = tran.numero;
            obj.concepto = tran.concepto;
            obj.idcuenta_bono = tran.idcuenta_bono;
            obj.idcuenta_desc = tran.idcuenta_desc;

            $uibModalInstance.close(obj);
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);

}());
