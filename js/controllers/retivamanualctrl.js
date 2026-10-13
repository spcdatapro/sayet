(function () {

    var controller = angular.module('cpm.retivamanual', []);

    controller.controller('retIvaManualCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'clienteSrvc', 'ventaSrvc', '$filter', '$confirm', 'toaster',
        function ($scope, authSrvc, empresaSrvc, clienteSrvc, ventaSrvc, $filter, $confirm, toaster) {

            // variables
            $scope.empresas = [];
            $scope.clientes = [];
            $scope.facturas = [];
            $scope.cargando = false;
            var usuario = {};
            $scope.empresa = '';
            $scope.formularios = []

            // mantener una copia original y detectar modificaciones solo para registros existentes
            $scope._originalFormulario = null;

            $scope.formulario = { fecha: moment().toDate() };

            authSrvc.getSession().then(usr => {
                // globalizar usuario
                usuario = usr;

                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(d => {
                    empresaSrvc.getEmpresaUsuario(usr.uid).then(autorizado => {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });

                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                        // traer formularios
                        $scope.getHistorial(usr.workingon.toString(), $scope.formulario.fecha);
                    });
                });

                // asignar empresa automatico
                $scope.formulario.idempresa = usr.workingon.toString();
            });

            clienteSrvc.lstCliente().then(d => $scope.clientes = d);

            $scope.$watchGroup(['formulario.idcliente', 'formulario.fecha', 'formulario.idempresa'], datos => {
                if ($scope.formulario.id > 0 && $scope.formulario.id) {
                    return;
                }

                if (datos[0] && datos[1] && datos[2]) {
                    // formatear fecha
                    const fechastr = moment(datos[1]).format('YYYY-MM-DD');

                    // traer facturas del cliente
                    ventaSrvc.factClienteFecha(datos[0], fechastr, datos[2]).then(d => $scope.facturas = d);
                }

            })

            $scope.resetParams = idempresa => {
                $scope.formulario = { fecha: moment().toDate(), idempresa: idempresa };
            }

            $scope.getHistorial = (idempresa, fecha) => {
                // obtener anio de la fecha, para registros
                const anio = moment(fecha).year();
                if (anio >= 2000) {
                    $scope.cargando = true;
                    $scope.resetParams(idempresa);

                    // globalizar nombre de la empresa
                    $scope.empresa = $filter('getById')($scope.empresas, idempresa).nomempresa;

                    // traer formularios de la empresa
                    ventaSrvc.getFormularios(idempresa, anio).then(f => {
                        $scope.formularios = f;
                        $scope.cargando = false;
                    })
                }
            }

            $scope.getDetalle = f => {
                f.monto = +f.monto;
                f.fecha = moment(f.fecha).toDate();
                $scope.formulario = f;
            }

            $scope.guardar = formulario => {
                $scope.cargando = true;
                formulario.fechastr = moment(formulario.fecha).format('YYYY-MM-DD');
                let option = formulario.id > 0 ? 'uriva' : 'ariva';

                ventaSrvc.editRow(formulario, option).then(d => {
                    if (d.tipo == 'success') {
                        $scope.getHistorial(formulario.idempresa);

                        $scope.getDetalle(d.formulario);

                        toaster.pop(d.tipo, "Retencion de IVA", d.mensaje);
                    } else {
                        toaster.pop(d.tipo, "Retencion de IVA", d.mensaje);
                    }

                    $scope.cargando = false;
                })
            }

            $scope.eliminar = formulario => {
                $confirm({
                    text: '¿Seguro(a) desea eliminar el formulario?',
                    title: 'Eliminar registro de formulario', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    ventaSrvc.editRow(formulario, 'driva_manual').then(d => {
                        toaster.pop(d.tipo, "Retencion de IVA", d.mensaje);
                        $scope.getHistorial(d.id);
                    })
                })
            }

            $scope.$watch('formulario.id', function (newId) {
                if (newId > 0) {
                    // cuando se carga un detalle existente, guardar snapshot y limpiar bandera
                    $scope._originalFormulario = angular.copy($scope.formulario);
                    $scope.modificaciones = false;
                } else {
                    // nuevo registro -> no consideramos modificaciones para habilitar el botón
                    $scope._originalFormulario = null;
                    $scope.modificaciones = true;
                }
            });

            $scope.$watch('formulario', function (nuevo) {
                if (!nuevo) {
                    $scope.modificaciones = true;
                    return;
                }

                if (nuevo.id > 0 && $scope._originalFormulario) {
                    $scope.modificaciones = !angular.equals(nuevo, $scope._originalFormulario);
                } else {
                    $scope.modificaciones = true;
                }
            }, true);
        }])
}())