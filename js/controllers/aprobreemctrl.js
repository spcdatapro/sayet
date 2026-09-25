(function () {

    const controller = angular.module('cpm.aprobreemctrl', []);

    controller.controller('aprobacionReembolso', ['$scope', 'reembolsoSrvc', '$confirm', '$filter', 'authSrvc', 'DTOptionsBuilder', '$uibModal', 'toaster',
        function ($scope, reembolsoSrvc, $confirm, $filter, authSrvc, DTOptionsBuilder, $uibModal, toaster) {

            $scope.reembolsos = [];
            $scope.usuario = {};
            $scope.permiso = {};
            $scope.info = 'Revisados';

            $scope.dtOptions = DTOptionsBuilder.newOptions().withBootstrap().withOption('paging', false).withOption('order', false);

            authSrvc.getSession().then(usr => {
                // traer los permisos
                authSrvc.gpr({ idusuario: usr.uid, ruta: window.location.hash.replace(/^#\//, '') }).then(d => $scope.permiso = d);
                $scope.usuario = usr;
                getPendientes();
            });

            function getPendientes() {
                reembolsoSrvc.reembolsosPendientes().then(d => { $scope.reembolsos = d; console.log(d); });
            }

            $scope.verDetReem = obj => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalDetalleReembolso.html',
                    controller: 'ModalDetReemCtrl',
                    windowClass: 'app-modal-window',
                    resolve: {
                        reembolso: obj
                    }
                });
            };

            $scope.verAdjuntos = obj => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalAdjuntosReembolso.html',
                    controller: 'ModalAdjuntoReemCtrl',
                    windowClass: 'app-modal-window',
                    resolve: {
                        reembolso: obj
                    }
                });
            };

            $scope.verRevision = obj => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalRevisionReembolso.html',
                    controller: 'ModalRevisionReemCtrl',
                    size: 'lg',
                    windowClass: 'modal-reembolso-grande',
                    resolve: {
                        reembolso: obj
                    }
                });
            };

            $scope.aprobar = (obj) => {
                if (+obj.aprobada === 1) {
                    $confirm({ text: '¿Esta seguro(a) de aprobar el reembolso No. ' + obj.id + '?', title: 'Aprobar reembolso', ok: 'Sí', cancel: 'No' }).then(function () {
                        obj.idusuario = $scope.usuario.uid;
                        reembolsoSrvc.editRow(obj, 'apr').then(function () {
                            getPendientes();
                            toaster.pop('info', 'Reembolso aprobado', 'Se aprobó el reembolso No. ' + obj.id, 'timeout:1500');
                        });
                    }, () => {
                        obj.aprobada = 0;
                    });
                }
            };

            $scope.denegar = (obj) => {
                if (+obj.denegada === 1) {
                    $confirm({ text: '¿Esta seguro(a) de denegar el reembolso No. ' + obj.id + '?', title: 'Denegar reembolso', ok: 'Sí', cancel: 'No' }).then(function () {
                        obj.idusuario = $scope.usuario.uid;
                        reembolsoSrvc.editRow(obj, 'ngr').then(function () {
                            getPendientes();
                            toaster.pop('info', 'Reembolso denegado', 'Se denegó el reembolso No. ' + obj.id, 'timeout:1500');
                        });
                    }, () => {
                        obj.revisada = 0;
                    });
                }
            };

            $scope.revisar = obj => {
                if (+obj.revisada === 1) {
                    $confirm({ text: '¿Esta seguro(a) de marcar como revisado el reembolso No. ' + obj.id + '? Esto notificará a la persona encargada de aprobar.', title: 'Revisar reembolso', ok: 'Sí', cancel: 'No' }).then(function () {
                        obj.idusuario = $scope.usuario.uid;
                        reembolsoSrvc.editRow(obj, 'rvr').then(function () {
                            getPendientes();
                            toaster.pop('info', 'Reembolso revisado', 'Se revisó el reembolso No. ' + obj.id, 'timeout:1500');
                        });
                    }, () => {
                        obj.revisada = 0;
                    });
                }
            }
        }]);

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalDetReemCtrl', ['$scope', '$uibModalInstance', 'reembolso', function ($scope, $uibModalInstance, reembolso) {
        $scope.compras = reembolso.compras;
        $scope.reembolso = reembolso;

        $scope.cancel = () => { $uibModalInstance.dismiss('cancel'); };
    }]);

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalAdjuntoReemCtrl', ['$scope', '$uibModalInstance', 'toaster', 'reembolso', 'reembolsoSrvc', '$uibModal', function ($scope, $uibModalInstance, toaster, reembolso, reembolsoSrvc, $uibModal) {
        $scope.reembolso = reembolso;
        $scope.lstadjuntos = [];

        $scope.loadAdjuntos = () => {
            reembolsoSrvc.lstReemAdjuntos($scope.reembolso.id).then((d) => $scope.lstadjuntos = d);
        };

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.loadAdjuntos();
    }]);

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalRevisionReemCtrl', ['$scope', '$uibModalInstance', 'toaster', 'reembolso', 'reembolsoSrvc', '$uibModal', function ($scope, $uibModalInstance, toaster, reembolso, reembolsoSrvc, $uibModal) {
        $scope.reembolso = reembolso;
        $scope.compras = reembolso.compras;
        $scope.lstadjuntos = [];
        $scope.ver_adjunto = false;

        $scope.loadAdjuntos = () => {
            reembolsoSrvc.lstReemAdjuntos($scope.reembolso.id).then((d) => $scope.lstadjuntos = d);
        };

        $scope.previewAdjunto = adjunto => {
            $scope.ver_adjunto = true;
            $scope.previewUrl = adjunto.ubicacion;
        }

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.loadAdjuntos();
    }]);
}());