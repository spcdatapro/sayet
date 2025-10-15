(function () {

    var controller = angular.module('cpm.vacacionesctrl', []);

    controller.controller('vacacionesCtrl', ['$scope', 'empleadoSrvc', 'planillaSrvc', '$filter', 'toaster', '$confirm', 'jsReportSrvc', '$window',
        function ($scope, empleadoSrvc, planillaSrvc, $filter, toaster, $confirm, jsReportSrvc, $window) {

            // variables
            $scope.empleados = [];
            $scope.historial = [];
            var asuetos = [];
            $scope.cargando = false;
            $scope.modificaciones = false;
            // ulitmo dia del anio
            const ultima_fecha = moment().endOf('year');

            // mantener una copia original y detectar modificaciones solo para registros existentes
            $scope._originalVacaciones = null;

            $scope.vacaciones = { anio: +moment().toDate().getFullYear() };
            $scope.empleado = '';

            empleadoSrvc.lstEmpleados().then(e => $scope.empleados = e);

            function getAsuetos(anio) {
                asuetos = [];
                planillaSrvc.getRegistroAsuetos(anio).then(a => {
                    a.forEach(asueto => {
                        let rango = {};
                        rango.del = moment(asueto.fechainicio).toDate();
                        rango.al = moment(asueto.fechafin).toDate();
                        asuetos.push(rango);
                    })
                })
            }

            // devuelve true si la fecha está dentro de algún rango de asuetos
            function esAsueto(fecha) {
                if (!fecha) { return false; }
                const d = moment(fecha).startOf('day').toDate();
                for (let r of asuetos) {
                    const del = moment(r.del).startOf('day').toDate();
                    const al = moment(r.al).startOf('day').toDate();
                    if (d >= del && d <= al) { return true; }
                }
                return false;
            }

            $scope.resetParams = (idempleado, historial = []) => {
                $scope.vacaciones = { anio: $scope.vacaciones.anio, idempleado: idempleado };

                // reinicar dias restantes
                if (historial.length > 0) {
                    calcularDiasRestantes(idempleado, historial);
                }
            }

            $scope.getHistorial = idempleado => {
                if ($scope.vacaciones.anio >= 2000) {
                    $scope.cargando = true;
                    $scope.resetParams(idempleado);
                    // globalizar nombre de empleado seleccionado
                    $scope.empleado = $filter('getById')($scope.empleados, idempleado).nombre;
                    getAsuetos($scope.vacaciones.anio);

                    // traer el historial de vacaciones del empleado
                    planillaSrvc.getHistorialVacaciones(idempleado, $scope.vacaciones.anio).then(h => {
                        $scope.historial = h;
                        // calculo de dias de vacaciones que le quedan en base a la fecha de ingreso y los dias que ha utilizado
                        calcularDiasRestantes(idempleado, h);
                        $scope.cargando = false;
                    })
                }
            }

            $scope.getDetalle = id => {
                planillaSrvc.getDetalleVacacion(id).then(d => {
                    d.fechainicio = moment(d.fechainicio).toDate();
                    d.fechafin = moment(d.fechafin).toDate();
                    d.dias = +d.dias;
                    d.anio = +d.anio;

                    $scope.vacaciones = d;
                })
            }

            $scope.guardar = vacaciones => {
                $scope.cargando = true;
                let option = vacaciones.id > 0 ? 'uvac' : 'avac';

                planillaSrvc.editRow(option, vacaciones).then(d => {
                    if (d.tipo == 'success') {
                        $scope.getHistorial(vacaciones.idempleado);

                        $scope.getDetalle(d.id);

                        toaster.pop(d.tipo, "Registro de vacaciones", d.mensaje);
                    } else {
                        toaster.pop(d.tipo, "Registro de vacaciones", d.mensaje);
                    }

                    $scope.cargando = false;
                })
            }

            $scope.eliminar = vacaciones => {
                $confirm({
                    text: '¿Seguro(a) desea eliminar el registro de vacaciones? Esto liberara los días restantes del empleado.',
                    title: 'Eliminar registro de vacación', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    planillaSrvc.editRow('dvac', vacaciones).then(d => {
                        toaster.pop(d.tipo, "Registro de vacaciones", d.mensaje);
                        $scope.getHistorial(d.id);
                    })
                })
            }

            $scope.printDetalle = params => {
                $scope.cargando = true;
                try {
                    jsReportSrvc.getPDFReport('rknrmwPzex', params).then(function (pdf) {
                        $window.open(pdf);
                        $scope.cargando = false;
                    })
                } catch (err) {
                    console.log(err);
                    $scope.cargando = false;
                }
            }

            function calcularDiasRestantes(idempleado, historial) {
                console.log(historial);
                console.log(asuetos);
                // traer fecha de ingreso del empleado
                const fecha_ingreso = $filter('getById')($scope.empleados, idempleado).ingreso;
                const anio = moment(fecha_ingreso).toDate().getFullYear();
                let dias_restantes = 0;

                if (+anio === +$scope.vacaciones.anio) {
                    // si el empleado ingreso el anio actual, hacer calculo para dias de vacaciones
                    dias_restantes = Math.floor((ultima_fecha.diff(fecha_ingreso, 'days') + 1) * 15 / 365);
                } else {
                    // de lo contrario utilizar los 15 dias de vacaciones de ley
                    dias_restantes = 15;
                }
                console.log(dias_restantes);

                if (historial.length > 0) {
                    // si tiene historial restar los dias que ha utilizado
                    historial.forEach(h => {
                        dias_restantes -= h.dias;
                    })
                }

                $scope.dias_restantes = dias_restantes;
            }

            $scope.$watchGroup(['vacaciones.fechainicio', 'vacaciones.fechafin'], function (fechas) {
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
                            if (diaSemana !== 0 && diaSemana !== 6 && !esAsueto(fechaActual)) {
                                dias++;
                            }
                            fechaActual.setDate(fechaActual.getDate() + 1);
                        }
                    }

                    $scope.vacaciones.dias = dias;
                } else {
                    $scope.vacaciones.dias = 0;
                }
            })

            $scope.$watch('vacaciones.id', function (newId) {
                if (newId > 0) {
                    // cuando se carga un detalle existente, guardar snapshot y limpiar bandera
                    $scope._originalVacaciones = angular.copy($scope.vacaciones);
                    $scope.modificaciones = false;
                } else {
                    // nuevo registro -> no consideramos modificaciones para habilitar el botón
                    $scope._originalVacaciones = null;
                    $scope.modificaciones = true;
                }
            });

            $scope.$watch('vacaciones', function (nuevo) {
                if (!nuevo) {
                    $scope.modificaciones = true;
                    return;
                }

                if (nuevo.id > 0 && $scope._originalVacaciones) {
                    $scope.modificaciones = !angular.equals(nuevo, $scope._originalVacaciones);
                } else {
                    $scope.modificaciones = true;
                }
            }, true);

            getAsuetos($scope.vacaciones.anio);

        }])
}())