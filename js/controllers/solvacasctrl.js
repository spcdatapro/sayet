(function () {

    var solvacascontroller = angular.module('cpm.solvacasctrl', []);

    solvacascontroller.controller('solVacasCtrl', ['$scope', 'empleadoSrvc', 'toaster', '$confirm', 'jsReportSrvc', function
        ($scope, empleadoSrvc, toaster, $confirm, jsReportSrvc) {

        // variables globales
        $scope.empleados = [];
        $scope.solicitud = { periodo: moment().toDate().getFullYear(), sabado: false, asuetos: [], medio: false };
        $scope.registros = { ver: false, nombre: 'No hay registros disponibles', cuantos: 0 };
        $scope.asuetos = [];

        // servicios para traer datos
        empleadoSrvc.lstEmpleados().then(function (d) { $scope.empleados = d; });

        $scope.reiniciar = function (idempleado = null, periodo = null) {
            $scope.registros = { ver: false, nombre: 'No hay registros disponibles', cuantos: 0 };

            if (idempleado > 0) {
                $scope.solicitud = { periodo: +periodo, idempleado: idempleado, asuetos: [], sabado: false, medio: false };
            } else {
                $scope.solicitud = { periodo: moment().toDate().getFullYear(), sabado: false, asuetos: [], medio: false };
            }
        }

        $scope.setCantidad = function (inicia, fin, sabados, medio) {
            if (fin) {
                const unDiaEnMilisegundos = 86400000; // 24 horas * 60 minutos * 60 segundos * 1000 milisegundos
                let dias = 0;
                // obtener el tipo de la fecha
                let fecha = new Date(inicia.getTime());
                while (fecha <= fin) {
                    // validar si se cuentan los sabdos y sumar el dia 
                    if (!sabados) {
                        if (fecha.getDay() !== 0 && fecha.getDay() !== 6) {
                            dias++;
                        }
                    } else {
                        if (fecha.getDay() !== 0) {
                            dias++;
                        }
                    }
                    // para cambiar de dia
                    fecha.setTime(fecha.getTime() + unDiaEnMilisegundos);
                }
                // restar cantidad de asuetos
                if ($scope.solicitud.asuetos.length > 0){
                    dias -= +$scope.solicitud.asuetos.length;
                }
                $scope.solicitud.cantidad = medio ? dias -= 0.5 : dias;
            } else if (inicia) {
                $scope.solicitud.cantidad = medio ? 0.5 : 1;
            } else {
                return;
            }
        }

        $scope.getRegistros = function (idempleado, periodo, ver) {
            $scope.reiniciar(idempleado, periodo);
            registros = $scope.registros;

            // para esconder tabla en lo que se busca
            registros.ver = ver;

            // traer registros atados al empleado
            empleadoSrvc.historialVacas(idempleado, periodo).then(function (d) {
                if (d.length > 0) {
                    registros.cuantos = d.length;
                    registros.nombre = 'Ver registros';
                    registros.data = d;
                }
            });
        }

        // d = $scope.solicitud
        $scope.guardar = function (d, act = false) {
            if ($scope.registros.data && !act) {
                let registros = $scope.registros.data;
                for (var i = 0; i < registros.length; i++) {
                    if (registros[i].fechaini == moment(d.fechaini).format('YYYY-MM-DD')) {
                        toaster.pop('error', 'El registro ya existe',
                            'No pueden haber dos registros con la misma fecha, favor revisar.', 'timeout:2000');
                        d.fechaini = undefined;
                        return;
                    }
                }
            }

            // formatear fecha
            d.fechainistr = moment(d.fechaini).format('YYYY-MM-DD');
            d.fechafinstr = d.fechafin ? moment(d.fechafin).format('YYYY-MM-DD') : undefined;
            if (d.asuetos.length > 0) {
                d.strasueto = d.asuetos.join(', ');
            }

            if (d.id > 0) {
                empleadoSrvc.actVacaciones(d).then(function (d) {
                    // sacar de array
                    d = d[0];

                    // convertir en fecha legible js
                    d.fecha = moment(d.fecha).toDate();
                    $scope.getRegistros(d.idempleado, d.periodo, $scope.registros.ver);
                    $scope.getRegistro(d.id);
                    toaster.pop('success', 'Actualizado con éxito', 'El registro se actualizó correctamente.', 'timeout:1500');
                });
            } else {
                empleadoSrvc.genVacaciones(d).then(function (d) {
                    // sacar de array
                    d = d[0];

                    $scope.getRegistros(d.idempleado, d.periodo, $scope.registros.ver);
                    $scope.getRegistro(d.id);
                    toaster.pop('success', 'Generado con éxito', 'El registro se guardo correctamente.', 'timeout:1500');
                });
            }
        }

        $scope.getRegistro = function (id) {
            empleadoSrvc.getRegistro(id).then(function (d) {
                // sacar de array la data
                d = d[0];

                // convertir fechas e int's
                d.fechaini = moment(d.fechaini).toDate();
                d.fechafin = moment(d.fechafin).toDate();
                d.periodo = +d.periodo;
                d.cantidad = +d.cantidad;
                d.medio = +d.medio; 
                d.sabado = +d.sabado;
                if (d.asuetos != null) {
                    // convertir str a array
                    d.asuetos = d.asuetos.split(',');

                    // llenar array de asuetos para poder modificar y mostrar
                    d.asuetos.forEach(fechastr => {
                        $scope.asuetos.push({fechastr});
                    });
                }

                $scope.solicitud = d;
            });
        }

        // para eliminar
        $scope.eliminar = function (obj) {
            $confirm({
                title: '¿Seguro desea eliminar el registro? ', ok: 'Sí', cancel: 'No',
                text: 'Se eliminará el registro de vaciones de Juan y liberará los días disponibles de vacaciones.'
            }).then(function () {
                empleadoSrvc.delRegistro(obj).then(function () {
                    $scope.getRegistros(obj.idempleado, obj.periodo, $scope.registros.ver);
                    $scope.reiniciar(obj.idempleado, obj.periodo);
                });
            });
        }

        $scope.pdf = function (params) {
            // formatear fecha
            params.fechainistr = moment(params.fechaini).format('YYYY-MM-DD');
            params.fechafinstr = params.fechafin ? moment(params.fechafin).format('YYYY-MM-DD') : undefined;

            // obtener reporte
            jsReportSrvc.getPDFReport('ryH4ZppYT', params).then(function (pdf) { $scope.content = pdf; });
        }

        // asuetos se manejan con un selector de fechas y un selector multiple, el selector de fecha empuja una fecha al selector
        // ya en el selector se puede eliminar alguna fecha 
        $scope.agregarAsueto = function (fecha) {
            fechastr = moment(fecha).format('DD/MM/YYYY');
            // crear objeto para mostrar en selector
            let empujar = {fechastr: fechastr};
            $scope.asuetos.push(empujar);

            // auto seleccionar asueto
            $scope.solicitud.asuetos.push(fechastr);
            $scope.fasueto = undefined;
            $scope.setCantidad($scope.solicitud.fechaini, $scope.solicitud.fechafin);
        }

    }]);

}());