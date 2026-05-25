(function () {

    var controller = angular.module('cpm.horariosctrl', []);

    controller.controller('horariosCtrl', ['$scope', 'planillaSrvc', 'toaster', '$confirm',
        function ($scope, planillaSrvc, toaster, $confirm) {

            const DIAS_SEMANA_MAP = [
                { campo: 'domingo', id: 1 },
                { campo: 'lunes', id: 2 },
                { campo: 'martes', id: 3 },
                { campo: 'miercoles', id: 4 },
                { campo: 'jueves', id: 5 },
                { campo: 'viernes', id: 6 },
                { campo: 'sabado', id: 7 }
            ];

            // variables
            $scope.dias_semana = [{ id: 1, nombre: 'Domingo' }, { id: 2, nombre: 'Lunes' }, { id: 3, nombre: 'Martes' }, { id: 4, nombre: 'Miércoles' },
            { id: 5, nombre: 'Jueves' }, { id: 6, nombre: 'Viernes' }, { id: 7, nombre: 'Sábado' }];
            $scope.cargando = false;
            $scope.horarios = [];

            $scope.guardar = horario => {
                // peparar dias
                DIAS_SEMANA_MAP.forEach(dia => {
                    horario[dia.campo] = horario.dias_semana.includes(dia.id) ? 1 : 0;
                });

                // formatear horas
                horario.delstr = moment(horario.del).format('HH:mm:ss');
                horario.alstr = moment(horario.al).format('HH:mm:ss');

                // validar si es insert o update
                let tipo = horario.id > 0 ? 'uhor' : 'ahor';

                planillaSrvc.editRow(tipo, horario).then(res => {
                    toaster.pop({ type: res.tipo, title: 'Horarios', body: res.mensaje });
                    $scope.getHorarios();
                })
            }

            $scope.getHorarios = () => {
                planillaSrvc.getHorarios().then(res => {
                    const horarios = Array.isArray(res) ? res : [res];

                    horarios.forEach(horario => {
                        horario.dias_semana = DIAS_SEMANA_MAP
                            .filter(dia => +horario[dia.campo] === 1)
                            .map(dia => dia.id);
                        horario.dias = $scope.dias_semana
                            .filter(dia => horario.dias_semana.includes(dia.id))
                            .map(dia => dia.nombre)
                            .join(', ');
                    });

                    $scope.horarios = horarios;
                })
            }

            $scope.getDetalle = horario => {
                horario.del = moment(horario.delstr, 'HH:mm:ss').toDate();
                horario.al = moment(horario.alstr, 'HH:mm:ss').toDate();
                $scope.horario = horario;
            }

            $scope.eliminar = id => {
                $confirm({ title: 'Eliminar Horario', text: '¿Seguro(a) desea eliminar este horario?', ok: 'Sí', cancel: 'No' })
                    .then(() => {
                        planillaSrvc.editRow('dhor', { id: id }).then(res => {
                            toaster.pop({ type: res.tipo, title: 'Horarios', body: res.mensaje });
                            $scope.getHorarios();
                        });
                    });
            }


            $scope.resetParams = () => {
                $scope.horario = {};
            }

            $scope.getHorarios();
        }])
}())