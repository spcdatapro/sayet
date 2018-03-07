(function(){

    var cpmidxctrl = angular.module('cpm.cpmidxctrl', ['cpm.authsrvc', 'toaster']);

    cpmidxctrl.controller('cpmIdxCtrl', ['$scope', '$rootScope', '$uibModal', '$window', 'authSrvc', 'toaster', 'empresaSrvc', '$interval', 'presupuestoSrvc', 'desktopNotification', '$confirm', function($scope, $rootScope, $uibModal, $window, authSrvc, toaster, empresaSrvc, $interval, presupuestoSrvc, desktopNotification, $confirm){
        $scope.tituloPagina = 'CPM - Bienvenido';

        $scope.menuUsr = [];
        $scope.qEmpresa = {};
        $scope.lasEmpresas = [];
        $scope.usr = {};
        $scope.notificaciones = [];
        var intervalo;

        empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; });

        function chkSolPago(){
            presupuestoSrvc.lstNotificaciones().then(function(d){
                var notificar = '';
                for(var i = 0; i < d.length; i++){
                    if(notificar != ''){ notificar += '\r\n'; }
                    notificar += d[i].notificacion;
                }
                if(notificar != ''){
                    desktopNotification.show('Nueva solicitud de pago de OT', {
                        icon: 'img/sayet.ico',
                        body: notificar,
                        onClick: function(){
                            var modalInstance = $uibModal.open({
                                animation: true,
                                templateUrl: 'modalNotificaciones.html',
                                controller: 'ModalNotificacionesCtrl',
                                resolve:{
                                    ots: function(){ return d; },
                                    usr: function(){ return $scope.usr; }
                                }
                            });

                            modalInstance.result.then(function(){ }, function(){ });
                        }
                    });
                }
            });
        }

        function chkVenceFactura(){
            if($scope.qEmpresa){
                if($scope.qEmpresa.formspend && +$scope.qEmpresa.formspend > 0 && +$scope.qEmpresa.formspend <= 20){
                    desktopNotification.show('Cantidad de facturas muy baja', {
                        icon: 'img/sayet.ico',
                        body: 'Quedan ' + $scope.qEmpresa.formspend + ' facturas por imprimir de ' + $scope.qEmpresa.nomempresa.trim() + '; favor prestar atención.'
                    });
                }

                if($scope.qEmpresa.mesesfaltan && +$scope.qEmpresa.mesesfaltan >= 0 && +$scope.qEmpresa.mesesfaltan <= 1){
                    desktopNotification.show('Vencimiento de facturas', {
                        icon: 'img/sayet.ico',
                        body: 'Queda un mes o menos para el vencimiento de las factuas de ' + $scope.qEmpresa.nomempresa.trim() + '; favor prestar atención.'
                    });
                }
            }
        }

        authSrvc.getSession().then(function(usrLogged){
            authSrvc.getMenu(parseInt(usrLogged.uid)).then(function(res){
                $scope.menuUsr = res;
                $scope.usr = usrLogged;

                if(parseInt(usrLogged.workingon) === 0){
                    authSrvc.getUltimaEmpresa(+$scope.usr.uid).then(function(ue){
                        var tmpworkingon = +ue.ultempre > 0 ? +ue.ultempre : 4;
                        empresaSrvc.getEmpresa(tmpworkingon).then(function(r){
                            $scope.qEmpresa = r[0];
                            authSrvc.setEmpresaSess(r[0].id).then(function(s){ $rootScope.workingon = parseInt(s.workingon); });
                            chkVenceFactura();
                        });
                    });
                }else{
                    empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                        $scope.qEmpresa = r[0];
                        chkVenceFactura();
                    });
                }

                authSrvc.getPermiso($scope.usr.uid, 'tranbanc', 'c').then(function(d){
                    if(+d.permiso == 1){
                        chkSolPago();
                        intervalo = $interval(chkSolPago, (120 * 60000));
                    }
                });
            });
        });

        $scope.$watch('qEmpresa', function(newValue, oldValue) {
            var oldEmp = oldValue.nomempresa != null && oldValue.nomempresa != undefined ? oldValue.nomempresa : 'ninguna';
            var msg = 'Cambió la empresa que está trabajando de '+ oldEmp + ' a ' + newValue.nomempresa;
            toaster.pop('info', 'Cambio de empresa de trabajo', msg);
        });

        $scope.openSetEmpresa = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalSelectEmpresa.html',
                controller: 'ModalInstanceCtrl',
                resolve:{
                    empresas: function(){ return $scope.lasEmpresas; }
                }
            });

            modalInstance.result.then(function(selectedItem){
                $scope.qEmpresa = selectedItem;
                authSrvc.setEmpresaSess(selectedItem.id).then(function(r){
                    $rootScope.workingon = parseInt(r.workingon);
                    $window.location.reload();
                });
            }, function(){
                toaster.pop('warning', 'Cambio de empresa de trabajo', 'Canceló el cambio de empresa...');
            });
        };

        $scope.doLogOut = function(){
            authSrvc.setUltimaEmpresa(+$scope.usr.uid, +$scope.qEmpresa.id).then(function(){
                authSrvc.doLogOut().then(function(res){
                    $rootScope.logged = false;
                    $rootScope.uid = 0;
                    $rootScope.fullname = null;
                    $rootScope.usuario = null;
                    $rootScope.correoe = null;
                    $rootScope.workingon = 0;
                    $window.location.href = 'index.html';
                });
                if(angular.isDefined(intervalo)){
                    $interval.cancel(intervalo);
                    intervalo = undefined;
                }
            });
        };
    }]);

    cpmidxctrl.controller('ModalInstanceCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'empresas', function($scope, $rootScope, $uibModalInstance, empresas){
        $scope.lasEmpresas = empresas;
        $scope.objEmpresa = {};

        $scope.seleccionada = true;

        $scope.yaSelecciono = function(){

            if($rootScope.workingon)

            $scope.seleccionada = !($scope.objEmpresa.id != null && $scope.objEmpresa.id != undefined);
        };

        $scope.ok = function () {
            $uibModalInstance.close($scope.objEmpresa);
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);

    cpmidxctrl.controller('ModalNotificacionesCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'presupuestoSrvc', 'ots', 'usr', function($scope, $rootScope, $uibModalInstance, presupuestoSrvc, ots, usr){
        $scope.ots = ots;

        $scope.ok = function () {
            presupuestoSrvc.setNotificado(usr.uid).then(function(){ $uibModalInstance.close(); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);

}());
