(function () {

    const controller = angular.module('cpm.aprobarctrl', []);

    controller.controller('aprobarCtrl', ['$scope', 'tranBancSrvc', 'DTOptionsBuilder', 'authSrvc', 'empresaSrvc', '$route', 'toaster', '$uibModal',
        function ($scope, tranBancSrvc, DTOptionsBuilder, authSrvc, empresaSrvc, $route, toaster, $uibModal) {

            $scope.transacciones = [];
            $scope.empresas = [];
            $scope.usrdata = {};
            $scope.params_trans = { fdel: moment().toDate(), fal: moment().toDate(), revisando: true };
            $scope.total = 0;

            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withBootstrap()
                .withOption('pagination', true)
                .withOption('order', [[2, 'desc']]);

            authSrvc.getSession().then((usr) => {
                $scope.usrdata = usr;
                authSrvc.gpr({ idusuario: parseInt(usr.uid), ruta: $route.current.params.name })
                    .then(d => {
                        $scope.operacion = !d.m ? 'Revisar' : 'Aprobar';
                        $scope.params_trans.revisando = !d.m;
                    });
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(usr.uid)
                        .then(function (autorizado) {
                            let idempresas = [];
                            autorizado.forEach(aut => { idempresas.push(aut.id) });

                            $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                            $scope.getPendientes();
                        });
                });
                $scope.usr = usr;
            });

            $scope.getPendientes = () => {
                $scope.params_trans.fdelstr = moment($scope.params_trans.fdel).format('YYYY-MM-DD');
                $scope.params_trans.falstr = moment($scope.params_trans.fal).format('YYYY-MM-DD');
                tranBancSrvc.getPendientesAprobar($scope.params_trans)
                    .then(d => {
                        d.forEach(tr => {
                            const monto = +tr.monto;
                            const promedio = +tr.promedio;

                            const diff = Math.abs(monto - promedio);
                            tr.diferenciaPorcentaje = +((diff / promedio) * 100).toFixed(2);
                            // marcar si la diferencia excede el 15%
                            tr.desviado = tr.diferenciaPorcentaje > 2;
                        });
                        console.log(d);
                        $scope.transacciones = d;
                    })
            }

            $scope.aprobar = p => {
                if (p.aprobada) {
                    $scope.total += +p.monto;
                } else {
                    $scope.total -= +p.monto;
                }
            }

            $scope.verHistorial = (historial, promedio) => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalHistorial.html',
                    controller: 'ModalHistorial',
                    windowClass: 'app-modal-window',
                    resolve: {
                        historial: () => historial,
                        promedio: () => promedio
                    }
                })
            }

            $scope.aceptar = () => {
                let aenviar = [];
                const fecha = moment().format('YYYY-MM-DD');
                $scope.transacciones.forEach(t => {
                    if (t.aprobada) {
                        aenviar.push({ id: t.id, idusuario: $scope.usrdata.uid, fecha: fecha })
                    }
                })

                let endpoint = $scope.params_trans.revisando ? 'revtran' : 'auttran';
                tranBancSrvc.editRow(aenviar, endpoint).then(d => {
                    toaster.pop({ type: 'success', title: $scope.operacion + ' transacciones', body: 'El proceso ha finalizado correctamente.' });
                    $scope.total = 0;
                    $scope.getPendientes();
                });
            }

        }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalHistorial', ['$scope', '$uibModalInstance', 'historial', 'promedio',
        function ($scope, $uibModalInstance, historial, promedio) {

            $scope.historial = historial;
            $scope.promedio = promedio;

            $scope.cancel = () => { $uibModalInstance.dismiss('cancel'); };

        }]);
}());
