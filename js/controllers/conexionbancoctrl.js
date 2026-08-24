(function () {

    var controller = angular.module('cpm.conexionbanco', []);

    controller.controller('conBancoCtrl', ['$scope', 'tranBancSrvc', 'bancoSrvc', '$interval', 'toaster', '$http', '$q', '$window', 'authSrvc', 'empresaSrvc',
        ($scope, tranBancSrvc, bancoSrvc, $interval, toaster, $http, $q, $window, authSrvc, empresaSrvc) => {

            // variables
            $scope.cuentas = [];
            $scope.paramsBI = { fal: moment().subtract(1, 'day').toDate() };
            $scope.paramsGYT = { fecha: moment().subtract(1, 'day').toDate() };
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

            $scope.traerDocsGYT = params => {
                $scope.progress = 0;
                $scope.cargando = true;
                params.fechastr = moment(params.fecha).format('DDMMYYYY');
                estatusCarga(25);

                try {
                    // traer token de GYT
                    tranBancSrvc.tokenGYT().then(d => {
                        toaster.pop({ type: d.tipo, title: 'Token', body: d.mensaje, timeout: 10000 });
                        if (d.exito) {
                            params.token = d.token;
                            estatusCarga(50);
                            // generar estado de cuenta
                            try {
                                estatusCarga(75);
                                tranBancSrvc.generarGYT(params).then(d => {
                                    toaster.pop({ type: d.tipo, title: 'Estado de cuenta', body: d.mensaje, timeout: 60000 });
                                    if (d.exito) {
                                        try {
                                            tranBancSrvc.trasladarGYT(params).then(d => {
                                                toaster.pop({ type: d.tipo, title: 'Traslado de estado de cuenta', body: d.mensaje, timeout: 10000 });
                                                if (d.exito) {
                                                    params.archivo = d.archivo;
                                                    estatusCarga(100);
                                                    try {
                                                        tranBancSrvc.estadoCtaGYT(params).then(d => {
                                                            toaster.pop({ type: d.tipo, title: 'Estado de cuenta', body: d.mensaje, timeout: 60000 });
                                                        });
                                                    } catch (error) {
                                                        toaster.pop({ type: 'error', title: 'Error', body: 'Error en la comunicación, favor comunicarse con IT.', timeout: 10000 });
                                                        console.error(error);
                                                        $scope.cargando = false;
                                                    }
                                                } 
                                            });
                                        } catch (error) {
                                            toaster.pop({ type: 'error', title: 'Error', body: 'Error en la comunicación, favor comunicarse con IT.', timeout: 10000 });
                                            console.error(error);
                                            $scope.cargando = false;
                                        }
                                    }
                                });
                            } catch (error) {
                                toaster.pop({ type: 'error', title: 'Error', body: 'Error en la comunicación, favor comunicarse con IT.', timeout: 10000 });
                                console.error(error);
                                $scope.cargando = false;
                            }
                        }
                    });
                } catch (error) {
                    toaster.pop({ type: 'error', title: 'Error', body: 'Error en la comunicación, favor comunicarse con IT.', timeout: 10000 });
                    console.error(error);
                    $scope.cargando = false;
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
