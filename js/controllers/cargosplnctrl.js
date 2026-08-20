(function () {

    var controller = angular.module('cpm.cargosplnctrl', []);

    controller.controller('cargosPlnCtrl', ['$scope', 'planillaSrvc', '$uibModal', 'toaster', 'authSrvc', 'empresaSrvc', '$confirm',
        ($scope, planillaSrvc, $uibModal, toaster, authSrvc, empresaSrvc, $confirm) => {

            $scope.idusuario = undefined;
            $scope.cargos = [];
            $scope.generando = false;
            $scope.generados = [];
            $scope.empresas = [];
            $scope.par_premios = { anio: moment().year() };

            authSrvc.getSession().then(usuario => {
                $scope.idusuario = usuario.uid;
                empresaSrvc.lstEmpresas().then(d => {
                    $scope.empresas = d;
                    $scope.par_premios.idempresa = usuario.workingon > 0 ? usuario.workingon.toString() : undefined;
                });
            });

            function getPendientes() {
                planillaSrvc.getPendientes().then((d) => {
                    d.forEach(data => {
                        data.total = (+data.finiquito + +data.vacaciones + +data.aguinaldo + +data.bono + +data.ordinario + +data.extra + +data.otrosbono)
                            - (+data.prestamos + +data.anticipos + +data.otrosdesc);
                    });
                    $scope.cargos = d;
                });

                $scope.getPremios();
            }

            $scope.genTranFiniquito = (data) => {
                data.premio = false;
                modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalTranban.html',
                    controller: 'ModalTranban',
                    windowClass: 'app-modal-window',
                    resolve: {
                        data: data
                    }
                }).result.then(obj => {
                    $scope.generando = true;
                    obj.idusuario = $scope.idusuario;
                    planillaSrvc.generaTranFiniquito(obj).then((d) => {
                        getPendientes();
                        $scope.generando = false;
                        toaster.pop({ type: d.tipo, title: 'Generación de transacción', body: d.mensaje, timeout: 10000 });
                    });
                });
            };

            // premios pendientes
            $scope.premios = [];
            $scope.otros = true;

            $scope.$watch('par_premios.anio', (newVal) => {
                if (newVal != moment().year()) {
                    $scope.otros = false;
                } else {
                    $scope.otros = true;
                }
            });

            $scope.getPremios = () => {
                planillaSrvc.getPremios($scope.par_premios).then(d => {
                    $scope.premios = d;
                })
            }

            $scope.genTranPremio = data => {
                data.premio = true;

                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalTranban.html',
                    controller: 'ModalTranban',
                    windowClass: 'app-modal-window',
                    resolve: {
                        data: data
                    }
                }).result.then(obj => {
                    $scope.generando = true;
                    obj.idusuario = $scope.idusuario;
                    planillaSrvc.generaTranPremio(obj).then(d => {
                        getPendientes();
                        $scope.generando = false;
                        toaster.pop({ type: d.tipo, title: 'Generación de transacción', body: d.mensaje, timeout: 10000 });
                    });
                });
            };

            $scope.eliminarCargo = idcargo => {
                $confirm({
                    text: 'Este proceso eliminará el el cargo pendiente de indeminización. ¿Seguro(a) de continuar?',
                    title: 'Eliminar indemnización pendiente', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    // codigo si afirmativo 
                    planillaSrvc.editRow('dfin', { id: idcargo }).then(d => {
                        getPendientes();
                        toaster.pop({ type: d.tipo, title: 'Eliminar indemnización pendiente', body: d.mensaje, timeout: 10000 });
                    });
                });
            }

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
