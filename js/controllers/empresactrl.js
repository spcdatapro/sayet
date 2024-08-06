(function () {

    var empresactrl = angular.module('cpm.empresactrl', ['cpm.empresasrvc']);

    empresactrl.controller('empresaCtrl', ['$scope', 'empresaSrvc', 'monedaSrvc', 'tipoConfigContaSrvc', 'cuentacSrvc',
        '$confirm', '$uibModal', function ($scope, empresaSrvc, monedaSrvc, tipoConfigContaSrvc, cuentacSrvc, $confirm, $uibModal) {

            $scope.laEmpresa = { propia: 1, retenedora: 0 };
            $scope.lstEmpresas = [];
            $scope.lasMonedas = [];
            $scope.editando = false;
            $scope.etiqueta = "";
            $scope.laConfConta = {};
            $scope.lasConfsConta = [];
            $scope.lasCtasMov = [];
            $scope.losTiposConf = [];
            $scope.detConf = {};

            monedaSrvc.lstMonedas().then(function (d) {
                $scope.lasMonedas = d;
            });

            $scope.prepData = function (d) {
                for (var x = 0; x < d.length; x++) {
                    d[x].id = parseInt(d[x].id);
                    d[x].propia = parseInt(d[x].propia);
                    d[x].correlafact = parseInt(d[x].correlafact);
                    d[x].ultimocorrelativofact = parseInt(d[x].ultimocorrelativofact);
                    d[x].fechavencefact = moment(d[x].fechavencefact).isValid() ? moment(d[x].fechavencefact).toDate() : null;
                    d[x].ndplanilla = parseInt(d[x].ndplanilla);
                    d[x].retenedora = +d[x].retenedora;
                }
                return d;
            };

            $scope.getLstEmpresas = function () { empresaSrvc.lstEmpresas().then(function (d) { $scope.lstEmpresas = $scope.prepData(d); }); };

            $scope.resetEmpresa = function () {
                $scope.laEmpresa = {
                    id: 0, nomempresa: null, abreviatura: null, propia: 1, nit: null, direccion: null, idmoneda: '1', seriefact: null, correlafact: null, fechavencefact: null, ultimocorrelativofact: null,
                    retenedora: 0
                };
            };

            $scope.getEmpresa = function (idempresa) {
                empresaSrvc.getEmpresa(+idempresa).then(function (d) {
                    $scope.laEmpresa = $scope.prepData(d)[0];
                    $scope.getConfigConta($scope.laEmpresa);
                });
            };

            $scope.setData = function (obj) {
                obj.propia = obj.propia !== null && obj.propia != undefined ? obj.propia : 0;
                obj.seriefact = obj.seriefact != null && obj.seriefact != undefined ? obj.seriefact : '';
                obj.correlafact = obj.correlafact != null && obj.correlafact != undefined ? obj.correlafact : 0;
                obj.fechavencefactstr = moment(obj.fechavencefact).isValid() ? moment(obj.fechavencefact).format('YYYY-MM-DD') : '';
                obj.ultimocorrelativofact = obj.ultimocorrelativofact != null && obj.ultimocorrelativofact != undefined ? obj.ultimocorrelativofact : 0;
                obj.ndplanilla = obj.ndplanilla != null && obj.ndplanilla != undefined ? obj.ndplanilla : 0;
                obj.retenedora = obj.retenedora !== null && obj.retenedora != undefined ? obj.retenedora : 0;
                return obj;
            };

            $scope.addEmpresa = function (obj) {
                obj = $scope.setData(obj);
                empresaSrvc.editRow(obj, 'c').then(function (d) {
                    $scope.getLstEmpresas();
                    $scope.getEmpresa(d.lastid);
                });
            };

            $scope.updEmpresa = function (obj) {
                obj = $scope.setData(obj);
                empresaSrvc.editRow(obj, 'u').then(function () {
                    $scope.getLstEmpresas();
                    $scope.getEmpresa(obj.id);
                });
            };

            $scope.delEmpresa = function (id) {
                empresaSrvc.editRow({ id: id }, 'd').then(function () {
                    $scope.getLstEmpresas();
                });
            };

            $scope.getLstConfigConta = function (idempresa) {
                empresaSrvc.lstConfigConta(idempresa).then(function (det) { $scope.lasConfsConta = det; });
            };

            $scope.getConfigConta = function (objEmpresa) {
                $scope.editando = true;
                $scope.etiqueta = objEmpresa;
                tipoConfigContaSrvc.lstTiposConfigConta().then(function (d) { $scope.losTiposConf = d; });
                cuentacSrvc.getByTipo(parseInt(objEmpresa.id), 0).then(function (d) { $scope.lasCtasMov = d; });
                $scope.getLstConfigConta(parseInt(objEmpresa.id));
                goTop();
            };

            $scope.addConfCont = function (obj) {
                obj.idempresa = parseInt($scope.etiqueta.id);
                obj.idtipoconfig = parseInt(obj.objTipoConf.id);
                obj.idcuentac = parseInt(obj.objCuenta[0].id);
                empresaSrvc.editRow(obj, 'cc').then(function () {
                    $scope.getLstConfigConta(parseInt($scope.etiqueta.id));
                    $scope.detConf = {};
                    $scope.searchcta = "";
                });
            };

            $scope.delConfConta = function (idconf) {
                $confirm({ text: '¿Seguro(a) de eliminar esta configuración?', title: 'Eliminar configuración contable', ok: 'Sí', cancel: 'No' }).then(function () {
                    empresaSrvc.editRow({ id: idconf }, 'dc').then(function () { $scope.getLstConfigConta($scope.etiqueta.id); });
                });
            };

            $scope.getLstEmpresas();
            $scope.resetEmpresa();

            $scope.verPermisos = function () {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalUsuario.html',
                    controller: 'ModalPermisos',
                    resolve: {
                        empresa: () => $scope.laEmpresa
                    }
                });

                modalInstance.result.then(function (obj) {
                    console.log(obj);
                }, function () { return 0; });
            };

        }]);

    //-------------------------------------------------------------------------------------------------------------------------------
    empresactrl.controller('ModalPermisos', ['$scope', '$uibModalInstance', 'empresa', 'empresaSrvc', 'authSrvc', 'toaster',
        function ($scope, $uibModalInstance, empresa, empresaSrvc, authSrvc, toaster) {

            $scope.empresa = empresa;
            $scope.usuarios = [];
            $scope.asignados = [];
            $scope.idusuario = undefined;

            function getAsignados(idempresa) {
                empresaSrvc.getUsuarios(idempresa).then(function (d) {
                    $scope.asignados = d;
                    lstUsuarios();
                });
            }

            $scope.setUsuario = function (id) {
                $scope.idusuario = id;
            }

            function lstUsuarios() {
                authSrvc.lstPerfiles().then(function (d) {
                    let excluido = [];
                    $scope.asignados.forEach(usuario => {
                        excluido.push(usuario.id);
                    });
                    excluido.push('1');

                    $scope.usuarios = d.filter(usuario => !excluido.includes(usuario.id));
                });
            }

            $scope.asignarUsuario = function (idusuario, idempresa) {
                empresaSrvc.agregarPermiso(idusuario, idempresa).then(function (d) {
                    toaster.pop({
                        type: d.tipo, title: 'Permisos de empresa',
                        body: d.mensaje, timeout: 10000
                    });
                    $scope.idusuario = undefined;
                    getAsignados(idempresa);
                });
            }

            $scope.quitarUsuario = function (id) {
                empresaSrvc.quitarPermiso(id).then(function (d) {
                    toaster.pop({
                        type: d.tipo, title: 'Permisos de empresa',
                        body: d.mensaje, timeout: 10000
                    });
                    getAsignados(empresa.id);
                });
            }

            $scope.cancel = function () {
                $uibModalInstance.dismiss('cancel');
            };

            getAsignados(empresa.id);

        }]);

}());

