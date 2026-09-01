(function () {

    var controller = angular.module('cpm.conexionbanco', []);

    controller.controller('conBancoCtrl', ['$scope', 'tranBancSrvc', 'bancoSrvc', '$interval', 'toaster', '$http', '$q', '$window', 'authSrvc', 'empresaSrvc',
        ($scope, tranBancSrvc, bancoSrvc, $interval, toaster, $http, $q, $window, authSrvc, empresaSrvc) => {

            // variables
            $scope.cuentas = [];
            $scope.paramsBI = { fal: moment().subtract(1, 'day').toDate() };
            $scope.paramsGYT = { fecha: moment().subtract(1, 'day').toDate(), idcuenta: null };
            $scope.progress = 0;

            bancoSrvc.lstBancosMT940().then(d => {
                $scope.cuentas = d.filter(cuenta => cuenta.idbancopais == 1);
            });

            $scope.traerDocsBI = () => {
                $scope.progress = 0;
                $scope.cargando = true;
                estatusCarga(99);

                tranBancSrvc.concectarBancoBI().then(d => {
                    estatusCarga(100);
                    $scope.cargando = false;
                    toaster.pop({ type: d.tipo, title: 'Conexion a banco', body: d.mensaje, timeout: 10000 });
                });
            }

            $scope.traerDocsGYT = async params => {
                const payload = angular.copy(params || {});

                if (!payload || !payload.fecha || !payload.idcuenta) {
                    toaster.pop({ type: 'warning', title: 'Banco G&T', body: 'Debe seleccionar la cuenta y la fecha para continuar.', timeout: 6000 });
                    return;
                }

                $scope.progress = 0;
                $scope.cargando = true;
                payload.idcuenta = Number(payload.idcuenta);
                payload.fechastr = moment(payload.fecha).format('DDMMYYYY');
                const fecha_db = moment(payload.fecha).format('YYYY-MM-DD');

                try {
                    const f = await tranBancSrvc.lastFechaGYT(fecha_db);
                    let fechaDel = moment(f || fecha_db).startOf('day');
                    let fechaActual = moment(fechaDel).add(1, 'day');
                    let fechaFinal = moment(payload.fecha).startOf('day');
                    const totalDias = Math.max(1, fechaFinal.diff(fechaActual, 'days') + 1);
                    let indiceDia = 0;

                    while (fechaActual.isSameOrBefore(fechaFinal)) {
                        indiceDia++;
                        let paramsDia = angular.copy(payload);
                        paramsDia.fecha = moment(fechaActual).toDate();
                        paramsDia.fechastr = moment(fechaActual).format('DDMMYYYY');

                        let porcentaje = Math.min(99, Math.round((indiceDia / totalDias) * 99));
                        if (indiceDia === totalDias) {
                            porcentaje = 99;
                        }
                        estatusCarga(porcentaje);

                        const tokenRes = await tranBancSrvc.tokenGYT();
                        toaster.pop({ type: tokenRes.tipo, title: 'Token', body: tokenRes.mensaje, timeout: 10000 });

                        if (!tokenRes.exito) {
                            fechaActual.add(1, 'day');
                            continue;
                        }

                        paramsDia.token = tokenRes.token;

                        const generarRes = await tranBancSrvc.generarGYT(paramsDia);
                        toaster.pop({ type: generarRes.tipo, title: 'Estado de cuenta', body: generarRes.mensaje, timeout: 30000 });

                        if (!generarRes.exito) {
                            fechaActual.add(1, 'day');
                            continue;
                        }

                        const trasladoRes = await tranBancSrvc.trasladarGYT(paramsDia);
                        toaster.pop({ type: trasladoRes.tipo, title: 'Traslado de estado de cuenta', body: trasladoRes.mensaje, timeout: 10000 });

                        if (trasladoRes.exito) {
                            paramsDia.archivo = trasladoRes.archivo;
                            const estadoRes = await tranBancSrvc.estadoCtaGYT(paramsDia);
                            toaster.pop({ type: estadoRes.tipo, title: 'Estado de cuenta', body: estadoRes.mensaje, timeout: 10000 });
                        }

                        fechaActual.add(1, 'day');
                    }

                    $scope.cargando = false;
                } catch (error) {
                    $scope.cargando = false;
                    toaster.pop({ type: 'error', title: 'Error', body: 'Error en la comunicación, favor comunicarse con IT.', timeout: 10000 });
                    console.error(error);
                }
            }

            function estatusCarga(limite) {
                $interval(() => {
                    if ($scope.progress < limite) {
                        $scope.progress += 0.5;
                    } else {
                        return;
                    }
                }, 500)
            }

        }]);
}());
