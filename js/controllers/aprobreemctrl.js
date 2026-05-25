(function () {

    const controller = angular.module('cpm.aprobreemctrl', []);

    controller.controller('aprobacionReembolso', ['$scope', 'reembolsoSrvc', '$confirm', '$filter', 'authSrvc', 'DTOptionsBuilder', '$uibModal', 'toaster', function ($scope, reembolsoSrvc, $confirm, $filter, authSrvc, DTOptionsBuilder, $uibModal, toaster) {

        $scope.reembolsos = [];
        $scope.usuario = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withBootstrap().withOption('paging', false).withOption('order', false);

        authSrvc.getSession().then((usrLogged) => {
            $scope.usuario = usrLogged;
            getPendientes();
        });

        function getPendientes() {
            reembolsoSrvc.reembolsosPendientes().then(d => $scope.reembolsos = d);
        }

        $scope.verDetReem = obj => {
            $uibModal.open({
                animation: true,
                templateUrl: 'modalDetalleReembolso.html',
                controller: 'ModalDetReemCtrl',
                windowClass: 'app-modal-window',
                resolve: {
                    reembolso: () => obj
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
                    reembolso: () => obj
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
                }, function () {
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
                }, function () {
                    obj.denegada = 0;
                });
            }
        };
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
}());