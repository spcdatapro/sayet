angular.module('cpm')
    .controller('MntEmpleadoController', ['$scope', '$http', 'empServicios', 'empresaSrvc', 'proyectoSrvc', 'cuentacSrvc', 'pstServicios', 'unidadSrvc', '$confirm', '$uibModal', 'planillaSrvc', 'municipioSrvc', '$filter', 'toaster', 'jsReportSrvc', '$window',
        function ($scope, $http, empServicios, empresaSrvc, proyectoSrvc, cuentacSrvc, pstServicios, unidadSrvc, $confirm, $uibModal, planillaSrvc, municipioSrvc, $filter, toaster, jsReportSrvc, $window) {
            $scope.formulario = false;
            $scope.empleados = [];
            $scope.inicio = 0;
            $scope.hay = false;
            $scope.archivos = [];
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.cuentas = [];
            $scope.puestos = [];
            $scope.archivotipo = [];
            $scope.empresasPlanilla = [];
            $scope.bitacora = [];
            $scope.unidades = [];
            $scope.movimiento = [];
            $scope.movRango = false;
            $scope.bita = {}
            $scope.movEditar = false;
            $scope.movProcesando = false;
            $scope.index = undefined;
            $scope.prueba = true;
            $scope.nacionalidades = [];
            $scope.municipios = [];
            $scope.discapacidades = [];
            $scope.nivel_educacion = [];
            $scope.castas = [];
            $scope.lenguas = [];
            $scope.puestosnue = [];
            $scope.copiado = false;
            $scope.per = { idnacionalidad: '83', iddiscapacidad: '1' }
            $scope.ver_activos = 1;
            let actuales = {};
            $scope.cargando = false;
            // variables para paginas
            $scope.currentPage = 1; // Página actual
            $scope.itemsPerPage = 10; // Número de elementos por página
            $scope.lookFor = ''; // Busqueda
            $scope.ver_activos = '1';
            $scope.eliminable = false;
            $scope.form_bitacora = false;

            // para pginas de resultados
            $scope.$watch('empleados.length', function () {
                $scope.totalPages = Math.ceil($scope.empleados.length / $scope.itemsPerPage);
            });

            $scope.$watch('lookFor', function () {
                // Calcula el número total de páginas después del filtro
                $scope.totalPages = Math.ceil($scope.filteredEmpleados().length / $scope.itemsPerPage);
                // Reinicia la página actual a la primera después del filtro
                $scope.currentPage = 1;
            });

            $scope.setPage = function (page) {
                if (page >= 1 && page <= $scope.totalPages) {
                    $scope.currentPage = page;
                }
            };

            $scope.paginatedEmpleados = function () {
                var filtered = $scope.filteredEmpleados();
                var start = ($scope.currentPage - 1) * $scope.itemsPerPage;
                return filtered.slice(start, start + $scope.itemsPerPage);
            };

            $scope.filteredEmpleados = function () {
                return $scope.empleados.filter(function (e) {
                    return !$scope.lookFor || Object.keys(e).some(function (key) {
                        return String(e[key]).toLowerCase().includes($scope.lookFor.toLowerCase());
                    });
                });
            };

            $scope.totalPages = Math.ceil($scope.empleados.length / $scope.itemsPerPage);
            // fin de paginas

            empServicios.getNacionalidades().then(d => { $scope.nacionalidades = d; });
            municipioSrvc.lstAllMunicipios().then(d => { $scope.municipios = d; });
            empServicios.getDiscapacidades().then(d => { $scope.discapacidades = d; });
            empServicios.getNivelEducacion().then(d => { $scope.nivel_educacion = d; });
            empServicios.getCasta().then(d => { $scope.castas = d; });
            empServicios.getLenguas().then(d => { $scope.lenguas = d; });
            empServicios.getPuestos().then(d => { $scope.puestosnue = d; });
            empServicios.getCatalogo().then(data => { $scope.movimiento = data.movimiento });

            $scope.mostrarForm = function () {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalCreacion.html',
                    controller: 'ModalCreacionCtrl',
                    resolve: {
                        empresas: () => $scope.empresasPlanilla
                    }
                }).result.then(function (data) {
                    $scope.cargando = true;
                    console.log(data);
                    empServicios.nuevoEmpleado(data).then(d => {
                        toaster.pop({ type: d.tipo, title: 'Nuevo empleado', body: d.mensaje, timeout: 10000 });
                        $scope.cargando = false;
                        $scope.buscar(1);
                        $scope.getEmpleado(d.id);
                    })
                })
                goTop();
            };

            $scope.cargoEmpresa = function (idempresa) {
                proyectoSrvc.lstProyectosPorEmpresa(idempresa).then(function (d) {
                    $scope.proyectos = d;
                });
                cuentacSrvc.getByTipo(idempresa, 0).then(function (d) { $scope.cuentas = d; });
            }

            $scope.copiar = async function (id, confirmacion) {
                try {
                    let text = document.getElementById(id).innerText;
                    await navigator.clipboard.writeText(text);
                    boton = document.getElementById(confirmacion);
                    boton.textContent = 'Texto copiado';
                    setTimeout(() => { boton.textContent = ''; }, 1000);
                } catch (err) {
                    console.error('Error al copiar el texto: ', err);
                }
            }

            $scope.guardar = function (emp, traer = false) {
                console.log(emp);
                console.log(traer);
                // campos para bitacora
                // $scope.emp.idplnmovimiento = emp.idplnmovimiento > 0 ? emp.idplnmovimiento : '11';
                // $scope.emp.fechatmp = emp.fechatmp > 0 ? emp.fechatmp : moment().toDate();
                // $scope.emp.fintmp = null;
                // $scope.emp.movfecha = emp.movfecha > 0 ? emp.movfecha : $scope.formatoFecha(emp.fechatmp);
                // $scope.emp.movobservaciones = emp.movobservaciones ? emp.movobservaciones : 'Modificacion a la ficha del empleado.';

                if ($scope.emp.fchnac) {
                    $scope.emp.fechanacimiento = $scope.formatoFecha($scope.emp.fchnac);
                } else {
                    $scope.emp.fechanacimiento = 0
                }

                if ($scope.emp.fching) {
                    $scope.emp.ingreso = $scope.formatoFecha($scope.emp.fching);
                } else {
                    $scope.emp.ingreso = 0
                }

                if ($scope.emp.fchrei) {
                    $scope.emp.reingreso = $scope.formatoFecha($scope.emp.fchrei);
                } else {
                    $scope.emp.reingreso = 0
                }

                if ($scope.emp.fchbaj) {
                    $scope.emp.baja = $scope.formatoFecha($scope.emp.fchbaj);
                } else {
                    $scope.emp.baja = 0
                }

                // mantener actualizada los datos laborales de nueva ficha
                if ($scope.emp.idplnmovimiento == 7) {
                    let lab = {};
                    lab.idplnempleado = $scope.emp.id;
                    lab.sueldo = $scope.emp.sueldo;
                    lab.bonificacionley = $scope.emp.bonificacionley;
                    lab.descuentoisr = $scope.emp.descuentoisr;

                    $scope.addDatosLaborales(lab, false);
                }

                empServicios.guardar(emp).then(function (data) {
                    alert(data.mensaje);
                    const nombre = $scope.emp.nombre;
                    $scope.hay = true;
                    $scope.emp = {};

                    if (data.up == 0) {
                        $scope.empleados.push(data.emp);
                    }

                    $scope.getEmpleado(emp.id);

                    $scope.editando = false;
                    // $scope.getBitacora($scope.emp.id);
                });
            };

            $scope.buscar = function (activo) {
                // datos para buscar
                let datos = { 'sin_limite': true, 'estatus': activo };

                empServicios.buscar(datos).then(function (data) {
                    data.resultados.forEach(d => {
                        d.primernombre = d.primernombre ? d.primernombre : '';
                        d.segundonombre = d.segundonombre ? d.segundonombre : '';
                        d.tercernombre = d.tercernombre ? d.tercernombre : '';
                        d.nombre = d.primernombre + ' ' + d.segundonombre + ' ' + d.tercernombre;

                        d.apellidocasada = d.apellidocasada ? d.apellidocasada : '';
                        d.primerapellido = d.primerapellido ? d.primerapellido : '';
                        d.segundoapellido = d.segundoapellido ? d.segundoapellido : '';
                        d.apellidos = d.primerapellido + ' ' + d.segundoapellido + d.apellidocasada;

                        d.puesto = d.idplnpuesto ? $filter('getById')($scope.puestos, d.idplnpuesto).descripcion : undefined;
                    });

                    $scope.empleados = data.resultados;
                });
            };

            $scope.getEmpleado = function (idempleado) {
                // nuevo 
                $scope.form_laboral = false;
                $scope.form_personal = false;
                $scope.form_emergencia = false;

                empServicios.getEmpleado(idempleado).then((d) => {

                    d.lab = !d.lab ? {} : d.lab;

                    // formatear datos personales
                    d.per.nacimiento = d.per.nacimiento ? moment(d.per.nacimiento).toDate() : undefined;
                    d.per.sexo = d.per.sexo === 'hombre' ? '1' : d.per.sexo === 'mujer' ? '2' : undefined;
                    d.per.estadocivil = d.per.estadocivil === 'soltero' ? '1' : d.per.estadocivil === 'casado' ? '2' : d.per.estadocivil === 'unido' ? '3' : undefined;
                    d.per.tipodoc = d.per.tipodoc === 'dpi' ? '1' : d.per.tipodoc === 'certificado de nacimiento' ? '2' : d.per.tipodoc === 'pasaporte' ? '3' : undefined;

                    // formatear datos laborales 
                    d.lab.ingreso = d.lab.ingreso ? moment(d.lab.ingreso).toDate() : undefined;
                    d.lab.reingreso = d.lab.reingreso ? moment(d.lab.reingreso).toDate() : undefined;
                    d.lab.baja = d.lab.baja ? moment(d.lab.baja).toDate() : undefined;
                    d.lab.sueldo = d.lab.sueldo ? +d.lab.sueldo : undefined;
                    d.lab.bonificacionley = d.lab.bonificacionley ? +d.lab.bonificacionley : undefined;
                    d.lab.porcentajeigss = d.lab.porcentajeigss ? +d.lab.porcentajeigss : undefined;
                    d.lab.descuentoisr = d.lab.descuentoisr ? +d.lab.descuentoisr : undefined;
                    d.lab.frecuencia = d.lab.frecuencia == 'quincenal' ? '1' : d.lab.frecuencia == 'mensual' ? '2' : undefined;
                    d.lab.metodo = d.lab.metodo == 'cheque' ? '1' : d.lab.metodo == 'efectivo' ? '2' : d.lab.metodo == 'nota debito' ? '3' : undefined;
                    d.lab.jornada = d.lab.jornada == 'diurna' ? '1' : d.lab.jornada == 'mixta' ? '2' : d.lab.jornada == 'noctura' ? '3' : d.lab.jornada == 'no esta sujeto a jornada' ? '4' : undefined;
                    d.lab.tipocontrato = d.lab.tipocontrato == 'verbal' ? '1' : d.lab.tipocontrato == 'escrito' ? '2' : undefined;
                    d.lab.temporalidad = d.lab.temporalidad == 'indefinido' ? '1' : d.lab.temporalidad == 'definido' ? '2' : undefined;

                    // globalizar las variables
                    $scope.per = d.per;
                    $scope.emp = d.emp;
                    $scope.lab = d.lab;
                    $scope.emg = d.emg;

                    // Para resumen de empleado
                    $scope.emp.dpi = d.per.documento;
                    $scope.emp.nit = d.per.nit;
                    $scope.emp.direccion = d.per.direccion;
                    $scope.emp.nompuesto = $scope.emp.idplnpuesto ? $filter('getById')($scope.puestos, $scope.emp.idplnpuesto).descripcion : null;
                    $scope.emp.nomempresa = $scope.lab.idempresadebito ? $filter('getById')($scope.empresas, $scope.lab.idempresadebito).nomempresa : null;
                    $scope.emp.estatus = $scope.emp.activo == 1 ? 'Activo' : 'Inactivo';
                    $scope.emp.fecha_activo = $scope.lab.reingreso != null ? $scope.lab.reingreso : $scope.lab.ingreso;
                    $scope.emp.fecha_baja = $scope.lab.baja != null ? $scope.lab.baja : null;
                    $scope.emp.igss = $scope.lab.igss != null ? $scope.lab.igss : '';
                    $scope.emp.nacimiento = $scope.per.nacimiento != null ? $scope.per.nacimiento : '';
                    $scope.emp.nombre = d.per.primernombre + ' ' + d.per.segundonombre + ' ' + d.per.tercernombre;
                    $scope.emp.apellidos = d.per.primerapellido + ' ' + d.per.segundoapellido + ' ' + d.per.apellidocasada;

                    // para permitir eliminar el empleado cuando esta en tiempo de prueba
                    const hoy = moment().toDate();
                    console.log(moment(d.lab.ingreso).diff(hoy, 'months'));
                    $scope.eliminable = moment(hoy).diff(d.lab.ingreso, 'months') <= 2 ? true : false;

                    $scope.formulario = true;
                    $scope.hay = true;
                    $scope.getArchivos($scope.emp.id);
                    $scope.getBitacora($scope.emp.id);
                    if ($scope.lab.idproyecto) {
                        $scope.setUnidades($scope.lab.idproyecto);
                    }
                    if ($scope.lab.idempresadebito) {
                        $scope.cargoEmpresa($scope.lab.idempresadebito);
                    }

                    goTop();
                });
            }

            $scope.nuevoMovimiento = () => {
                $scope.movRango = false;
                $scope.bita = {}
                $scope.movEditar = false;
            }

            $scope.editarMovimiento = function (index) {
                $scope.form_bitacora = true;
                $scope.bita = $scope.bitacora[index];
                $scope.movEditar = true;

                $scope.bita.movgasolina = parseFloat($scope.bita.movgasolina)
                $scope.bita.movdepvehiculo = parseFloat($scope.bita.movdepvehiculo)
                $scope.bita.movotros = parseFloat($scope.bita.movotros)
                $scope.bita.movdias = parseFloat($scope.bita.movdias)

                if ($scope.bita.movfecha) {
                    $scope.bita.fechatmp = $scope.formatoFechajs($scope.bita.movfecha)
                }

                if ($scope.bita.movfechafin) {
                    $scope.bita.fintmp = $scope.formatoFechajs($scope.bita.movfechafin)
                }

                $scope.tipoMovimiento();
            }

            $scope.guardarMovimiento = function (datos) {
                $scope.movProcesando = true

                if (!datos.idplnempleado) {
                    datos.idplnempleado = $scope.emp.id
                }

                if (datos.fechatmp) {
                    datos.movfecha = $scope.formatoFecha(datos.fechatmp)
                }

                if (datos.fintmp) {
                    datos.movfechafin = $scope.formatoFecha(datos.fintmp)
                }

                delete datos.nusuario;
                delete datos.apellidos;
                delete datos.dpi;
                delete datos.movimiento;

                // console.log(datos);

                empServicios.guardarBitacora(datos).then(function (res) {
                    $scope.bita = {};
                    $scope.movEditar = false;
                    $scope.tipoMovimiento()
                    $scope.getBitacora($scope.emp.id);
                    $scope.movProcesando = false;
                });
            }

            $scope.anularMovimiento = (data) => {
                let confirmText = '¿Seguro(a) desea eliminar el movimiento?';
                let confirmTitle = 'Eliminar movimiento';

                if (+data.idplnmovimiento === 12) {
                    confirmText += ' Esto eliminará la depreciación/gasolina de todos los movimientos.';
                } else if (+data.revertir === 1) {
                    confirmText += ' Esto revertirá algunos cambios.';
                }

                $confirm({
                    text: confirmText,
                    title: confirmTitle, ok: 'Sí', cancel: 'No'
                }).then(() => {
                    data.mostrar = 0;
                    planillaSrvc.anularBitacora(data).then(d => {
                        $scope.getEmpleado(d.empleado);
                        toaster.pop({ type: d.tipo, title: "Eliminar bitacora", body: d.mensaje, timeout: 10000 });
                    });
                    $scope.bita = {};
                });
            }

            $scope.getBitacora = emp => {
                $scope.bita = {};
                empServicios.getBitacora(emp).then(function (data) {
                    if (data.length > 0) {
                        data[0].anular = true;
                        data.forEach(item => {
                            if (+item.idplnmovimiento === 12) {
                                item.anular = true;
                            }
                        });
                        $scope.bitacora = data;
                    } else {
                        $scope.bitacora = [];
                    }
                });
            }

            $scope.agregarArchivo = function (arc) {
                if ($scope.emp.id) {
                    var $btn = $("#btnAgregarArchivo").button('loading');

                    arc.vence = $scope.formatoFecha(arc.fchvence);

                    empServicios.agregarArchivo($scope.emp.id, arc).then(function (data) {
                        $scope.getArchivos();
                        alert(data.mensaje);
                        $btn.button('reset');

                    });
                }
            }

            $scope.getArchivos = idempleado => {
                empServicios.getArchivos(idempleado).then(function (data) {
                    $scope.archivos = data.archivos;
                });
            }

            empresaSrvc.lstEmpresas().then(function (d) {
                $scope.empresas = d;
            });

            $scope.setUnidades = function (idproyecto) {
                unidadSrvc.lstUnidadesProy(idproyecto).then(function (d) {
                    $scope.unidades = d;
                });
            }

            pstServicios.lista().then(function (d) {
                $scope.puestos = d;
            });

            empServicios.getArchivoTipo().then(function (d) {
                $scope.archivotipo = d;
            });

            empServicios.getEmpresas().then(function (res) {
                $scope.empresasPlanilla = res.empresas
            })

            $scope.formatoFecha = function (fecha) {
                return fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate();
            };

            $scope.formatoFechajs = function (fecha) {
                var partes = fecha.split('-');
                return new Date(partes[0], partes[1] - 1, partes[2]);
            };

            $scope.tipoMovimiento = () => {
                $scope.movRango = false

                if ($scope.bita.idplnmovimiento !== undefined) {
                    for (var i = $scope.movimiento.length - 1; i >= 0; i--) {
                        if ($scope.movimiento[i].id == $scope.bita.idplnmovimiento) {
                            if ($scope.movimiento[i].rango_fecha == 1) {
                                $scope.movRango = true
                                break
                            }
                        }
                    }
                }
            }

            $scope.editar = () => {
                $confirm({
                    text: '¿Seguro(a) desea editar la ficha del empleado?',
                    title: 'Editar ficha del empleado', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    $scope.editando = !$scope.editando;
                });
            }

            $scope.darAlta = () => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalAlta.html',
                    controller: 'ModalAlta',
                    windowClass: 'app-modal-window',
                    resolve: {
                        idempleado: () => $scope.emp.id,
                        laboral: () => $scope.lab,
                        empresas: () => $scope.empresasPlanilla
                    }
                }).result.then(function (obj) {
                    empServicios.darAlta(obj).then((r) => {
                        toaster.pop({ type: r.tipo, title: 'Reingreso de empleado', body: r.mensaje, timeout: 10000 });

                        $scope.ver_activos = '1';
                        // $scope.buscar(1);

                        if (r.id) {
                            $scope.getEmpleado(r.id);
                        }
                    });
                });
            };

            $scope.addDatosPersonales = function (data, aceptar) {
                // revisar si existe o no
                let titulo = data.id ? 'Actualizacion de datos personales' : 'Creacion de datos personales';

                data.idplnempleado = $scope.emp.id > 0 ? $scope.emp.id : undefined;

                empServicios.editRow(data, 'c_per').then((r) => {
                    toaster.pop({ type: r.tipo, title: titulo, body: r.mensaje, timeout: 10000 });

                    if ($scope.emp.id > 0) {
                        idx = $scope.empleados.findIndex(empleado => empleado.id === r.id);

                        $scope.empleados[idx].nombre = r.nombre;
                        $scope.empleados[idx].apellidos = r.apellidos;
                        $scope.empleados[idx].direccion = r.direccion;
                        $scope.empleados[idx].telefono = r.telefono;
                    } else {
                        $scope.empleados.push({ id: r.id, nombre: r.nombre, apellidos: r.apellidos, direccion: r.direccion, telefono: r.telefono, puesto: '' });
                    }

                    if (r.id) {
                        $scope.getEmpleado(r.id);
                    }
                });

                if (aceptar) {
                    $scope.form_personal = false;
                    goTop();
                }
            }

            $scope.addDatosLaborales = function (data, aceptar) {
                // reivsar si existe para actualizar o crear
                let titulo = data.id ? 'Actalizacion de datos laborales' : 'Creacion de datos laborales';

                data.idplnempleado = $scope.emp.id > 0 ? $scope.emp.id : undefined;

                empServicios.editRow(data, 'c_lab').then((r) => {
                    toaster.pop({ type: r.tipo, title: titulo, body: r.mensaje, timeout: 10000 });

                    $scope.buscar(1);

                    if (r.id) {
                        $scope.getEmpleado(r.id);
                    }
                });

                if (aceptar) {
                    $scope.form_laboral = false;
                    goTop();
                }
            }

            $scope.addDatosEmergencia = function (data, aceptar) {
                // reivsar si existe para actualizar o crear
                let titulo = data.id ? 'Actalizacion de datos de emergencia' : 'Creacion de datos de emergencia';

                data.idplnempleado = $scope.emp.id > 0 ? $scope.emp.id : undefined;

                empServicios.editRow(data, 'c_emg').then((r) => {
                    toaster.pop({ type: r.tipo, title: titulo, body: r.mensaje, timeout: 10000 });

                    $scope.buscar(1);

                    if (r.id) {
                        $scope.getEmpleado(r.id);
                    }
                });

                if (aceptar) {
                    $scope.form_emergencia = false;
                    goTop();
                }
            }

            $scope.formPersonal = function (cancel, otro) {
                if (!cancel) {
                    $scope.editando = true;

                    if (!otro) {
                        if ($scope.form_laboral) {
                            $scope.formLaboral(false, true);
                        }

                        if ($scope.form_emergencia) {
                            $scope.formEmergencia(false, true);
                        }
                    }

                    if (!$scope.form_personal) {
                        actuales = angular.copy($scope.per);
                    }

                    if ($scope.form_personal) {
                        if (!_.isEqual(actuales, $scope.per)) {
                            if ($scope.per.id > 0) {
                                $confirm({
                                    text: '¿Desea guardar los cambios realizados a los datos personales?',
                                    title: 'Actualizar datos personales', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosPersonales($scope.per, otro);
                                }).catch(() => {
                                    $scope.getEmpleado($scope.emp.id);
                                });
                            } else {
                                $confirm({
                                    text: '¿Desea guardar los datos personales ingresados?',
                                    title: 'Guardar datos personales', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosPersonales($scope.per);
                                }).catch(() => {
                                    $scope.per = {};
                                });
                            }
                        }
                    }
                } else {
                    if ($scope.emp.id > 0) {
                        $scope.getEmpleado($scope.emp.id);
                    } else {
                        $scope.per = {};
                    }
                    $scope.editando = false;
                    $scope.form_personal = true;
                }
                $scope.form_personal = !$scope.form_personal;
                goTop();
            }

            $scope.formLaboral = function (cancel, otro) {
                if (!cancel) {
                    $scope.editando = true;
                    if (!otro) {
                        if ($scope.form_personal) {
                            $scope.formPersonal(false, true);
                        }
                        if ($scope.form_emergencia) {
                            $scope.formEmergencia(false, true);
                        }
                    }

                    if (!$scope.form_laboral) {
                        actuales = angular.copy($scope.lab);
                    }

                    if ($scope.form_laboral) {
                        if (!_.isEqual(actuales, $scope.lab)) {
                            if ($scope.lab.id > 0) {
                                $confirm({
                                    text: '¿Desea guardar los cambios realizados a los datos laborales?',
                                    title: 'Actualizar datos laborales', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosLaborales($scope.lab);
                                }).catch(() => {
                                    $scope.getEmpleado($scope.emp.id, otro);
                                });
                            } else {
                                $confirm({
                                    text: '¿Desea guardar los datos laborales ingresados?',
                                    title: 'Guardar datos laborales', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosLaborales($scope.lab);
                                }).catch(() => {
                                    $scope.lab = {};
                                });
                            }
                        }
                    }
                } else {
                    if ($scope.emp.id > 0) {
                        $scope.getEmpleado($scope.emp.id);
                    } else {
                        $scope.lab = {};
                    }
                    $scope.editando = false;
                    $scope.form_laboral = true;
                }
                $scope.form_laboral = !$scope.form_laboral;
                goTop();
            }

            $scope.formEmergencia = function (cancel, otro) {
                if (!cancel) {
                    $scope.editando = true;

                    if (!otro) {
                        if ($scope.form_personl) {
                            $scope.formPersonal(false, true);
                        }
                        if ($scope.form_laboral) {
                            $scope.formLaboral(false, true);
                        }
                    }

                    if (!$scope.form_emergencia) {
                        actuales = angular.copy($scope.emg);
                    }

                    if ($scope.form_emergencia) {
                        if (!_.isEqual(actuales, $scope.emg)) {
                            if ($scope.emg.id > 0) {
                                $confirm({
                                    text: '¿Desea guardar los cambios realizados a los datos de emergencia?',
                                    title: 'Actualizar datos de emergencia', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosEmergencia($scope.emg);
                                }).catch(() => {
                                    $scope.getEmpleado($scope.emp.id, otro);
                                });
                            } else {
                                $confirm({
                                    text: '¿Desea guardar los datos de emergencia ingresados?',
                                    title: 'Guardar datos de emergencia', ok: 'Sí', cancel: 'No'
                                }).then(() => {
                                    $scope.addDatosEmergencia($scope.emg);
                                }).catch(() => {
                                    $scope.lab = {};
                                });
                            }
                        }
                    }
                } else {
                    if ($scope.emp.id > 0) {
                        $scope.getEmpleado($scope.emp.id);
                    } else {
                        $scope.emg = {};
                    }
                    $scope.editando = false;
                    $scope.form_emergencia = true;
                }
                $scope.form_emergencia = !$scope.form_emergencia;
                goTop();
            }

            $scope.getRepEmpleador = () => {
                $uibModal.open({
                    animation: true,
                    templateUrl: 'modalReporteEmpleador.html',
                    controller: 'ModalReporteEmpleadorCtrl'
                }).result.then(function (anio) {
                    $scope.cargando = true;
                    jsReportSrvc.getReport('BkA5jnjK1x', anio).then(result => {
                        var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                        saveAs(file, 'Reporte_Empleador_' + anio + '.xlsx');
                        $scope.cargando = false;
                    }).catch(err => {
                        console.log(err);
                        toaster.pop({ type: 'error', title: 'Reporte empleador', body: 'Error en la conexion con el servidor, favor comunicarse con IT.', timeout: 7000 });
                        $scope.cargando = false;
                    });
                })
            }

            $scope.imprimirFicha = idempleado => {
                $scope.cargando = true;

                jsReportSrvc.getPDFReport('Sy0hlTcqyg', idempleado).then(function (pdf) {
                    $window.open(pdf);
                    $scope.cargando = false;
                }).catch(err => {
                    console.log(err);
                    toaster.pop({ type: 'error', title: 'Ficha de empleado', body: 'Error en la conexion con el servidor, favor comunicarse con IT.', timeout: 7000 });
                    $scope.cargando = false;
                });
            }

            $scope.imprimirContrato = idempleado => {
                $scope.cargando = true;

                const requiredFields = [
                    { field: $scope.per.nacimiento, message: 'No tiene fecha de nacimiento, favor ingresar.' },
                    { field: $scope.per.estadocivil, message: 'No tiene estadocivil, favor ingresar.' },
                    { field: $scope.per.sexo, message: 'No tiene género, favor ingresar.' },
                    { field: $scope.per.profesion, message: 'No tiene profesión, favor ingresar.' },
                    { field: $scope.per.documento, message: 'No tiene documento, favor ingresar.' },
                    { field: $scope.per.direccion, message: 'No tiene dirección, favor ingresar.' },
                    { field: $scope.lab.temporalidad, message: 'No tiene temporalidad, favor ingresar.' },
                    { field: $scope.lab.idpuesto, message: 'No tiene puesto, favor ingresar.' },
                    { field: $scope.lab.jornada, message: 'No tiene jornada, favor ingresar.' },
                ];

                // Loop through the required fields and check for errors
                for (const field of requiredFields) {
                    if (!field.field || (field.condition && !field.condition(field.field))) {
                        toaster.pop({ type: 'error', title: 'Contrato de empleado', body: field.message, timeout: 7000 });
                        return;
                    }
                }

                obj = { idempleado: idempleado, idrepresentante: 1 };

                if (!$scope.per.nacimiento) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene fecha de nacimiento, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.per.estadocivil) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene estadocivil, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.per.sexo) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene genero, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.per.profesion) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene profesion, favor ingresar.', timeout: 7000 });
                    return;
                } else if ($scope.per.nacionalidad == 0) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene nacionalidad, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.per.documento) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene documento, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.per.direccion) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene direccion, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.lab.temporalidad) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene temporalidad, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.lab.idpuesto) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene puesto, favor ingresar.', timeout: 7000 });
                    return;
                } else if (!$scope.lab.jornada) {
                    toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'No tiene jornada, favor ingresar.', timeout: 7000 });
                    return;
                } else {
                    jsReportSrvc.getPDFReport('S1nJHXuaJl', obj).then(function (pdf) {
                        $window.open(pdf);
                        $scope.cargando = false;
                    }).catch(err => {
                        console.log(err);
                        toaster.pop({ type: 'error', title: 'Contrato de empleado', body: 'Error en la conexion con el servidor, favor comunicarse con IT.', timeout: 7000 });
                        $scope.cargando = false;
                    });
                }
            }

            $scope.imprimirCarta = sueldo => {
                const params = { sueldo: sueldo, idempleado: $scope.emp.id };

                jsReportSrvc.getPDFReport('ryKOtgzMlx', params).then(function (pdf) {
                    $window.open(pdf);
                    $scope.cargando = false;
                }).catch(err => {
                    console.log(err);
                    toaster.pop({ type: 'error', title: 'Carta del empleado', body: 'Error en la conexion con el servidor, favor comunicarse con IT.', timeout: 7000 });
                    $scope.cargando = false;
                });
            }

            $scope.eliminar = idempleado => {
                $confirm({
                    text: '¿Seguro(a) de eliminar el empleado?',
                    title: 'Eliminar empleado', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    empServicios.eliminar(idempleado).then(function (d) {
                        toaster.pop({ type: d.tipo, title: 'Eliminacion de empleado', body: d.mensaje, timeout: 7000 });
                        $scope.hay = false;
                        $scope.formulario = false;
                        $scope.buscar(1);
                    })
                });
            }

            // $scope.buscar({});
            $scope.$watch('ver_activos', function (newValue, oldValue) {
                $scope.buscar(newValue);
            });
        }]
    )

    .controller('ModalAlta', ['$scope', '$uibModalInstance', 'idempleado', 'laboral', 'empresas', 'proyectoSrvc', 'cuentacSrvc', function
        ($scope, $uibModalInstance, idempleado, laboral, empresas, proyectoSrvc, cuentacSrvc) {
        $scope.params = laboral;
        $scope.empresas = empresas;
        $scope.params.reingreso = moment().toDate();
        $scope.params.idplnempleado = idempleado;
        $scope.cambio_empresa = false;
        $scope.proyectos = [];
        $scope.cuentas = [];
        const empresa_actual = laboral.idempresadebito;

        $scope.$watch('params.idempresadebito', (newValue) => {
            if (newValue !== undefined) {
                if (+empresa_actual !== +newValue) {
                    $scope.cambio_empresa = true;
                    proyectoSrvc.lstProyectosPorEmpresa(newValue).then(d => { $scope.proyectos = d })
                    cuentacSrvc.getByTipo(newValue, 0).then(d => { $scope.cuentas = d })
                } else {
                    $scope.cambio_empresa = false;
                }
            }
        });

        $scope.ok = function () { $uibModalInstance.close($scope.params); };

        $scope.cancel = () => { $uibModalInstance.dismiss('cancel'); };

    }])
    .controller('ModalCreacionCtrl', ['$scope', '$uibModalInstance', 'empresas', function
        ($scope, $uibModalInstance, empresas) {
        $scope.empleado = {};
        $scope.empresas = empresas;

        $scope.ok = function () { $uibModalInstance.close($scope.empleado); };

        $scope.cancel = () => { $uibModalInstance.dismiss('cancel'); };

    }])
    .controller('MntProsueldoController', ['$scope', '$http', 'empServicios', 'empresaSrvc',
        function ($scope, $http, empServicios, empresaSrvc) {
            $scope.resultados = false;
            $scope.proyecciones = [];

            $scope.buscar = function (datos) {
                empServicios.buscarProsueldo(datos).then(function (data) {
                    $scope.proyecciones = data;
                    $scope.resultados = true;
                });
            };

            $scope.actProsueldo = function (pro) {
                empServicios.guardarProsueldo(pro).then(function (data) {
                    console.log(data.mensaje);
                });
            };
        }
    ])
    .controller('MntPuestoController', ['$scope', '$http', 'pstServicios',
        function ($scope, $http, pstServicios) {
            $scope.formulario = false;
            $scope.resultados = false;
            $scope.puestos = [];
            $scope.inicio = 0;
            $scope.datosbuscar = [];
            $scope.buscarmas = true;
            $scope.hay = false;


            $scope.mostrarForm = function () {
                $scope.emp = {};
                $scope.formulario = true;
                $scope.hay = false;
            };

            $scope.guardar = function (emp) {
                pstServicios.guardar(emp).then(function (data) {
                    alert(data.mensaje);
                    $scope.hay = true;
                    $scope.pst = {};

                    if (data.up == 0) {
                        $scope.puestos.push(data.puesto);
                    }
                });
            };

            $scope.buscar = function (datos) {
                $scope.formulario = false;

                if (datos) {
                    $scope.datosbuscar = { 'inicio': 0, 'termino': datos.termino };
                } else {
                    $scope.datosbuscar = { 'inicio': 0 };
                }

                pstServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio = data.cantidad;
                    $scope.puestos = data.resultados;
                    $scope.resultados = true;

                    $scope.ocultarbtn(data.cantidad, data.maximo);
                });
            };

            $scope.mas = function () {
                pstServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio += parseInt(data.cantidad);

                    $scope.puestos = $scope.puestos.concat(data.resultados);

                    $scope.ocultarbtn(data.cantidad, data.maximo);
                });
            }

            $scope.ocultarbtn = function (cantidad, maximo) {
                if (parseInt(cantidad) < parseInt(maximo)) {
                    $scope.buscarmas = false;
                } else {
                    $scope.buscarmas = true;
                }
            }

            $scope.getPuesto = function (index) {
                $scope.pst = $scope.puestos[index];
                $scope.formulario = true;
                $scope.hay = true;
                goTop();
            };

            $scope.buscar({});
        }
    ])
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    .controller('ModalReporteEmpleadorCtrl', ['$scope', '$uibModalInstance', function ($scope, $uibModalInstance) {
        $scope.anio = moment().year() - 1;

        $scope.ok = anio => { $uibModalInstance.close(anio) }

        $scope.cancel = () => { $uibModalInstance.dismiss('cancel') }

    }])
    .controller('MntPeriodoController', ['$scope', '$http', 'periodoServicios',
        function ($scope, $http, periodoServicios) {
            $scope.formulario = false;
            $scope.resultados = false;
            $scope.periodos = [];
            $scope.inicio = 0;
            $scope.datosbuscar = [];
            $scope.buscarmas = true;
            $scope.hay = false;


            $scope.mostrarForm = function () {
                $scope.prd = {};
                $scope.formulario = true;
                $scope.hay = false;
            };

            $scope.guardar = function (prd) {
                prd.inicio = $scope.formatoFecha(prd.fecinicio)
                prd.fin = $scope.formatoFecha(prd.fecfin)

                periodoServicios.guardar(prd).then(function (data) {
                    alert(data.mensaje);
                    $scope.hay = true;
                    $scope.pst = {};

                    if (data.up == 0) {
                        $scope.periodos.push(data.puesto);
                    }
                });
            };

            $scope.buscar = function (datos) {
                $scope.formulario = false;

                if (datos) {
                    $scope.datosbuscar = { 'inicio': 0, 'cerrado': 0 };

                    if (datos.fecinicio) {
                        $scope.datosbuscar.inicio = $scope.formatoFecha(datos.fecinicio)
                    }

                    if (datos.fecfin) {
                        $scope.datosbuscar.fin = $scope.formatoFecha(datos.fecfin)
                    }

                    if (datos.cerrado == 1) {
                        $scope.datosbuscar.cerrado = 1;
                    }
                } else {
                    $scope.datosbuscar = { 'inicio': 0, 'cerrado': 1 };
                }

                periodoServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio = data.cantidad;
                    $scope.periodos = data.resultados;
                    $scope.resultados = true;
                    $scope.ocultarbtn(data.cantidad, data.maximo);
                });
            };

            $scope.mas = function () {
                periodoServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio += parseInt(data.cantidad);
                    $scope.periodos = $scope.periodos.concat(data.resultados);
                    $scope.ocultarbtn(data.cantidad, data.maximo);
                    $scope.$digest();
                });
            }

            $scope.ocultarbtn = function (cantidad, maximo) {
                if (parseInt(cantidad) < parseInt(maximo)) {
                    $scope.buscarmas = false
                } else {
                    $scope.buscarmas = true
                }
            }

            $scope.getPeriodo = function (index) {
                $scope.prd = $scope.periodos[index]
                $scope.prd.fecinicio = $scope.formatoFechajs($scope.prd.inicio)
                $scope.prd.fecfin = $scope.formatoFechajs($scope.prd.fin)
                $scope.prd.cerrado = parseInt($scope.prd.cerrado)
                $scope.formulario = true
                $scope.hay = true
                goTop()
            };

            $scope.formatoFecha = function (fecha) {
                return fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate();
            };

            $scope.formatoFechajs = function (fecha) {
                var partes = fecha.split('-');
                return new Date(partes[0], partes[1] - 1, partes[2]);
            };

            $scope.buscar({});
        }
    ]);
