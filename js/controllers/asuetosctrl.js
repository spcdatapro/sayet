(function () {

    var controller = angular.module('cpm.asuetosctrl', []);

    controller.controller('asuetosCtrl', ['$scope', 'planillaSrvc', 'toaster', '$confirm',
        function ($scope, planillaSrvc, toaster, $confirm) {

            // variables
            $scope.registro = [];
            $scope.cargando = false;
            $scope.modificaciones = false;
            $scope.dias_totales = 0;

            // mantener una copia original y detectar modificaciones solo para registros existentes
            $scope._originalAsueto = null;

            $scope.asueto = { anio: +moment().toDate().getFullYear() };

            $scope.resetParams = (idempleado, registro = []) => {
                $scope.asueto = { anio: $scope.asueto.anio };

                // reinicar dias restantes
                if (registro.length > 0) {
                    calcularDiasTotales(registro);
                }
            }

            $scope.getRegistro = anio => {
                if ($scope.asueto.anio >= 2000) {
                    $scope.cargando = true;
                    $scope.resetParams(anio);

                    // traer el registro de asuetos del anio
                    planillaSrvc.getRegistroAsuetos($scope.asueto.anio).then(r => {
                        $scope.registro = r;
                        // calculo de dias de vacaciones que le quedan en base a la fecha de ingreso y los dias que ha utilizado
                        calcularDiasTotales(r);
                        $scope.cargando = false;
                    })
                }
            }

            $scope.getDetalle = id => {
                planillaSrvc.getDetalleAsueto(id).then(d => {
                    d.fechainicio = moment(d.fechainicio).toDate();
                    d.fechafin = moment(d.fechafin).toDate();
                    d.dias = +d.dias;
                    d.anio = +d.anio;

                    $scope.asueto = d;
                })
            }

            $scope.guardar = asueto => {
                $scope.cargando = true;
                const option = asueto.id > 0 ? 'uasu' : 'aasu';

                planillaSrvc.editRow(option, asueto).then(d => {
                    if (d.tipo == 'success') {
                        $scope.getRegistro(asueto.anio);

                        toaster.pop(d.tipo, "Registro de vacaciones", d.mensaje);
                    } else {
                        toaster.pop(d.tipo, "Registro de vacaciones", d.mensaje);
                    }

                    $scope.cargando = false;
                })
            }

            $scope.eliminar = asueto => {
                $confirm({
                    text: '¿Seguro(a) desea eliminar el registro de asuetos? Esto liberara los días de ese registro.',
                    title: 'Eliminar registro de asueto', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    planillaSrvc.editRow('dasu', asueto).then(d => { 
                        toaster.pop(d.tipo, "Registro de asueto", d.mensaje);
                        $scope.getRegistro(d.id);
                    })
                })
            }

            function calcularDiasTotales(registro) {
                $scope.dias_totales = 0;
                if (registro.length > 0) {
                    // si tiene historial restar los dias que ha utilizado
                    registro.forEach(r => {
                        $scope.dias_totales += +r.dias;
                    })
                } else {
                    $scope.dias_totales = 0;
                }
            }

            $scope.$watchGroup(['asueto.fechainicio', 'asueto.fechafin'], function (fechas) {
                const [inicio, fin] = fechas;

                if (inicio && fin) {
                    const fechaInicio = new Date(inicio);
                    const fechaFin = new Date(fin);
                    let dias = 0;

                    // Asegurarse de que la fecha de inicio no sea mayor que la fecha de fin
                    if (fechaInicio <= fechaFin) {
                        let fechaActual = new Date(fechaInicio);

                        while (fechaActual <= fechaFin) {
                            const diaSemana = fechaActual.getDay(); // 0 = domingo, 6 = sábado
                            if (diaSemana !== 0 && diaSemana !== 6) {
                                dias++;
                            }
                            fechaActual.setDate(fechaActual.getDate() + 1);
                        }
                    }

                    $scope.asueto.dias = +dias;
                } else {
                    $scope.asueto.dias = 0;
                }
            })

            $scope.$watch('asueto.id', function (newId) {
                if (newId > 0) {
                    // cuando se carga un detalle existente, guardar snapshot y limpiar bandera
                    $scope._originalAsueto = angular.copy($scope.asueto);
                    $scope.modificaciones = false;
                } else {
                    // nuevo registro -> no consideramos modificaciones para habilitar el botón
                    $scope._originalAsueto = null;
                    $scope.modificaciones = true;
                }
            });

            $scope.$watch('asueto', function (nuevo) {
                if (!nuevo) {
                    $scope.modificaciones = true;
                    return;
                }

                if (nuevo.id > 0 && $scope._originalAsueto) {
                    $scope.modificaciones = !angular.equals(nuevo, $scope._originalAsueto);
                } else {
                    $scope.modificaciones = true;
                }
            }, true);

            $scope.getRegistro($scope.asueto.anio);

        }])
}())