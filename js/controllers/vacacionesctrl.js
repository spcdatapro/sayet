(function () {

    var controller = angular.module('cpm.vacacionesctrl', []);

    controller.controller('vacacionesCtrl', ['$scope', 'empleadoSrvc', 'planillaSrvc', '$filter',
        function ($scope, empleadoSrvc, planillaSrvc, $filter) {

            // variables
            $scope.empleados = [];
            $scope.historial = [];
            // ulitmo dia del anio
            const ultima_fecha = moment().endOf('year');

            $scope.vacaciones = { anio: +moment().toDate().getFullYear() };
            $scope.empleado = '';

            empleadoSrvc.lstEmpleados().then(e => $scope.empleados = e);

            $scope.getHistorial = idempleado => {
                // globalizar nombre de empleado seleccionado
                $scope.empleado = $filter('getById')($scope.empleados, idempleado).nombre;

                // traer el historial de vacaciones del empleado
                planillaSrvc.getHistorialVacaciones(idempleado, $scope.vacaciones.anio).then(h => {
                    $scope.historial = h;
                    // calculo de dias de vacaciones que le quedan en base a la fecha de ingreso y los dias que ha utilizado
                    calcularDiasRestantes(idempleado, h);
                })
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

            function calcularDiasRestantes (idempleado, historial) {
                // traer fecha de ingreso del empleado
                const fecha_ingreso = $filter('getById')($scope.empleados, idempleado).ingreso;
                const anio = moment(fecha_ingreso).toDate().getFullYear();
                let dias_restantes = 0;

                if (+anio === +$scope.vacaciones.anio) {
                    // si el empleado ingreso el anio actual, hacer calculo para dias de vacaciones
                    dias_restantes = Math.floor(ultima_fecha.diff(fecha_ingreso, 'days') * 15 / 365);
                } else {
                    // de lo contrario utilizar los 15 dias de vacaciones de ley
                    dias_restantes = 15;
                }

                if (historial.length > 0) {
                    // si tiene historial restar los dias que ha utilizado
                    historial.forEach(h => {
                        dias_restantes -= h.dias;
                    })
                }

                $scope.dias_restantes = dias_restantes;
            }

        }]);
}());