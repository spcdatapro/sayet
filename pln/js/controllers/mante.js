angular.module('cpm')
    .controller('MntEmpleadoController', ['$scope', '$http', 'empServicios', 'empresaSrvc', 'proyectoSrvc', 'cuentacSrvc', 'pstServicios', 'unidadSrvc', '$confirm', '$uibModal', 'planillaSrvc', 'municipioSrvc', '$filter', 'toaster',
        function ($scope, $http, empServicios, empresaSrvc, proyectoSrvc, cuentacSrvc, pstServicios, unidadSrvc, $confirm, $uibModal, planillaSrvc, municipioSrvc, $filter, toaster) {
            $scope.formulario = false;
            $scope.resultados = false;
            $scope.empleados = [];
            $scope.inicio = 0;
            $scope.datosbuscar = [];
            $scope.buscarmas = true;
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

            empServicios.getNacionalidades().then((d) => { $scope.nacionalidades = d; });
            municipioSrvc.lstAllMunicipios().then((d) => { $scope.municipios = d; });
            empServicios.getDiscapacidades().then((d) => { $scope.discapacidades = d; });
            empServicios.getNivelEducacion().then((d) => { $scope.nivel_educacion = d; });
            empServicios.getCasta().then((d) => { $scope.castas = d; });
            empServicios.getLenguas().then((d) => { $scope.lenguas = d; });
            empServicios.getPuestos().then((d) => { $scope.puestosnue = d; });

            $scope.mostrarForm = function () {
                $scope.emp = {};
                $scope.per = {};
                $scope.form_personal = false;
                $scope.emp = { idplnmovimiento: '10', movobservaciones: 'Contratación de nuevo empleado.' };
                $scope.formulario = true;
                $scope.hay = false;
                $scope.movRango = false;
                $scope.bita = {}
                $scope.movEditar = false;
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

            $scope.buscar = function (activo, traer = false) {
                // nuevo

                $scope.datosbuscar = { 'sin_limite': true, 'estatus': activo };

                // antes

                empServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio = data.cantidad;

                    data.resultados.forEach(d => {
                        if (d.primernombre && d.primerapellido) {
                            d.segundonombre = d.segundonombre ? d.segundonombre : '';
                            d.tercernombre = d.tercernombre ? d.tercernombre : '';
                            d.nombre = d.primernombre + ' ' + d.segundonombre + ' ' + d.tercernombre;
                        }

                        if (d.primerapellido && d.primernombre) {
                            d.apellidocasada = d.apellidocasada ? d.apellidocasada : '';
                            d.apellidos = d.primerapellido + ' ' + d.segundoapellido + d.apellidocasada;
                        }

                        if (d.dir) {
                            d.direccion = d.dir;
                        }

                        if (d.tel) {
                            d.telefono = d.tel;
                        }

                        if (d.correoe) {
                            d.correo = d.correoe;
                        }

                        d.puesto =
                            d.idpuesto ? $filter('getById')($scope.puestosnue, d.idpuesto).descripcion :
                                d.idplnpuesto ? $filter('getById')($scope.puestos, d.idplnpuesto).descripcion : undefined;
                    });
                    $scope.empleados = data.resultados;
                    $scope.resultados = true;

                    $scope.ocultarbtn(data.cantidad, data.maximo);

                    if (traer) {
                        $scope.getEmpleado(0);
                    }
                });
            };

            $scope.mas = function () {
                empServicios.buscar($scope.datosbuscar).then(function (data) {
                    $scope.datosbuscar.inicio += parseInt(data.cantidad);

                    $scope.empleados = $scope.empleados.concat(data.resultados);

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

            $scope.getEmpleado = function (idempleado, mismo) {
                $scope.form_personal = false;
                // nuevo 
                empServicios.getEmpleado(idempleado).then((d) => {
                    console.log(d);
                    // formatear datos personales
                    d.per = d.per ? d.per : {};
                    d.per.nacimiento = d.per.nacimiento ? moment(d.per.nacimiento).toDate() : undefined;
                    d.per.segundonombre = d.per.segundonombre ? d.per.segundonombre : '';
                    d.per.tercernombre = d.per.tercernombre ? d.per.tercernombre : '';
                    d.per.primerapellido = d.per.primerapellido ? d.per.primerapellido : '';
                    d.per.segundoapellido = d.per.segundoapellido ? d.per.segundoapellido : '';
                    d.per.apellidocasada = d.per.apellidocasada ? d.per.apellidocasada : '';
                    if (d.per.sexo) {
                        d.per.sexo = d.per.sexo == 'hombre' ? '1' : '2';
                    }
                    if (d.per.estadocivil) {
                        d.per.estadocivil = d.per.estadocivil == 'soltero' ? '1' : d.per.estadocivil == 'casado' ? '2' : '3';
                    }
                    if (d.per.tipodoc) {
                        d.per.tipodoc = d.per.tipodoc == 'dpi' ? '1' : d.per.estadocivil == 'certificado de nacimiento' ? '2' : '3';
                    }

                    // formatear datos laborales 
                    d.lab = d.lab ? d.lab : {};
                    d.lab.ingreso = d.lab.ingreso ? moment(d.lab.ingreso).toDate() : undefined;
                    d.lab.reingreso = d.lab.reingreso ? moment(d.lab.reingreso).toDate() : undefined;
                    d.lab.baja = d.lab.baja ? moment(d.lab.baja).toDate() : undefined;
                    d.lab.sueldo = d.lab.sueldo ? +d.lab.sueldo : undefined;
                    d.lab.bonificacionley = d.lab.bonificacionley ? +d.lab.bonificacionley : undefined;
                    d.lab.porcentajeigss = d.lab.porcentajeigss ? +d.lab.porcentajeigss : undefined;
                    d.lab.descuentoisr = d.lab.descuentoisr ? +d.lab.descuentoisr : undefined;
                    d.lab.frecuencia = d.lab.frecuencia == 'quincenal' ? '1' : d.lab.frecuencia == 'mensual' ? '2' : undefined;
                    d.lab.metodo = d.lab.metodo == 'cheque' ? '1' : d.lab.metodo == 'efectivo' ? '2'
                        : d.lab.metodo == 'nota debito' ? '3' : undefined;
                    d.lab.jornada = d.lab.jornada == 'diurna' ? '1' : d.lab.jornada == 'mixta' ? '2' :
                        d.lab.jornada == 'noctura' ? '3' : d.lab.jornada == 'no esta sujeto a jornada' ? '4' : undefined;
                    d.lab.tipocontrato = d.lab.tipocontrato == 'verbal' ? '1' : d.lab.tipocontrato == 'escrito' ? '2' :
                        undefined;
                    d.lab.temporalidad = d.lab.temporalidad == 'indefinido' ? '1' : d.lab.temporalidad == 'definido' ? '2' :
                        undefined;

                    $scope.per = d.per;
                    $scope.emp = d.emp;
                    $scope.lab = d.lab;
                    $scope.emg = d.emg;

                    $scope.emp.nombre = d.per.primernombre && d.per.primerapellido ? d.per.primernombre + ' ' + d.per.segundonombre + ' ' + d.per.tercernombre : $scope.emp.nombre;

                    $scope.emp.apellidos = d.per.primerapellido && d.per.primernombre ? d.per.primerapellido + ' ' + d.per.segundoapellido + ' ' + d.per.apellidocasada : $scope.emp.apellidos;

                    $scope.emp.dpi = d.per.documento ? d.per.documento : d.emp.dpi;

                    $scope.emp.nit = d.per.nit ? d.per.nit : d.emp.nit;

                    $scope.emp.direccion = d.per.direccion ? d.per.direccion : d.emp.direccion;

                    $scope.emp.nompuesto = d.lab.idpuesto > 0 ? $filter('getById')($scope.puestosnue, $scope.lab.idpuesto).descripcion :
                        $scope.emp.idplnpuesto ? $filter('getById')($scope.puestos, $scope.emp.idplnpuesto).descripcion : undefined;

                    $scope.emp.nomempresa = d.lab.idempresadebito > 0 ? $filter('getById')($scope.empresas, $scope.lab.idempresadebito).nomempresa :
                        $scope.emp.idempresa ? $filter('getById')($scope.empresas, $scope.emp.idempresadebito).nomempresa : undefined;

                    $scope.emp.estatus = $scope.emp.activo == 1 ? 'Activo' : 'Inactivo';

                    $scope.emp.descuentoisr = d.emp.descuentoisr > 0 ? +$scope.emp.descuentoisr : 0.00;
                    $scope.emp.bonificacionley = d.emp.bonificacionley > 0 ? parseFloat($scope.emp.bonificacionley) : 0.00;
                    $scope.emp.sueldo = d.emp.sueldo > 0 ? parseFloat($scope.emp.sueldo) : 0.00;
                    $scope.emp.porcentajeigss = d.emp.porcentajeigss > 0 ? parseFloat($scope.emp.porcentajeigss) : 0.00;
                    $scope.emp.activo = d.emp.baja ? 0 : 1;
                    if ($scope.lab.ingreso) {
                        $scope.emp.fecha_activo = $scope.lab.reingreso != null ? $scope.lab.reingreso : $scope.lab.ingreso;
                    } else {
                        $scope.emp.fecha_activo = $scope.emp.reingreso != null ? $scope.emp.reingreso : $scope.emp.ingreso;
                    }

                    if ($scope.emp.fechanacimiento) {
                        $scope.emp.fchnac = $scope.formatoFechajs($scope.emp.fechanacimiento);
                    }

                    if ($scope.emp.ingreso) {
                        $scope.emp.fching = $scope.formatoFechajs($scope.emp.ingreso);
                        let fecha_ingreso = moment($scope.emp.ingreso);
                        let fecha_actual = moment();
                        $scope.prueba = fecha_actual.diff(fecha_ingreso, 'month') <= 2 ? false : true;
                    }

                    if ($scope.emp.reingreso) {
                        $scope.emp.fchrei = $scope.formatoFechajs($scope.emp.reingreso);
                    }

                    if ($scope.emp.baja) {
                        $scope.emp.fchbaj = $scope.formatoFechajs($scope.emp.baja);
                    }

                    if (!mismo) {
                        $scope.form_emergencia = false;
                        $scope.form_laboral = false;
                        $scope.form_personal = false;
                    }

                    $scope.formulario = true
                    $scope.hay = true
                    $scope.getArchivos()
                    $scope.getBitacora($scope.emp.id)
                    $scope.getCatalogo()

                    if ($scope.lab.idproyecto) {
                        $scope.setUnidades($scope.lab.idproyecto);
                    } else {
                        $scope.setUnidades($scope.emp.idproyecto);
                    }

                    if ($scope.lab.idempresadebito > 0) {
                        $scope.cargoEmpresa($scope.lab.idempresadebito);
                    } else {
                        $scope.cargoEmpresa($scope.emp.idempresadebito);
                    }

                    goTop();
                });
                // antes
                // $scope.index = index;
                // $scope.emp = $scope.empleados[index];
            }

            $scope.nuevoMovimiento = () => {
                $scope.movRango = false;
                $scope.bita = {}
                $scope.movEditar = false;
            }

            $scope.editarMovimiento = function (index) {
                $scope.bita = $scope.bitacora[index]
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

                $scope.tipoMovimiento()
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
                if (confirm("Se anulará el registro, ¿Desea de continuar?")) {
                    data.mostrar = 0;
                    planillaSrvc.anularBitacora(data).then(d => { 
                        $scope.getEmpleado(d.empleado);
                        toaster.pop({ type: d.tipo, title: "Anulacion bitacora", body: d.mensaje, timeout: 10000 }) 
                    });
                    $scope.bita = {};
                }
            }

            $scope.getBitacora = function (emp) {
                empServicios.getBitacora(emp).then(function (data) {
                    if (data.length > 0) {
                        data[0].primero = true;
                        $scope.bitacora = data;
                    }
                });
            }

            $scope.getCatalogo = () => {
                empServicios.getCatalogo().then(function (data) {
                    $scope.movimiento = data.movimiento
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

            $scope.getArchivos = function () {
                if ($scope.emp.id) {
                    empServicios.getArchivos($scope.emp.id).then(function (data) {
                        $scope.archivos = data.archivos;
                    });
                }
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
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalAlta.html',
                    controller: 'ModalAlta',
                    windowClass: 'app-modal-window',
                    resolve: {
                        empleado: () => $scope.emp,
                        empresas: () => $scope.empresasPlanilla
                    }
                });

                modalInstance.result.then(function (obj) {
                    lab = {};   

                    obj.id = $scope.emp.id;
                    obj.idplnmovimiento = '6';
                    obj.fechatmp = moment().toDate();
                    obj.fintmp = null;
                    obj.movfecha = $scope.formatoFecha(obj.fechatmp);
                    obj.baja = 0;
                    obj.reingreso = $scope.formatoFecha(obj.fecha);
                    obj.activo = +1;
                    $scope.guardar(obj);

                    lab.id = $scope.emp.idlaboral;
                    lab.idplnempleado = $scope.emp.id;
                    lab.reingreso = obj.fecha;
                    lab.baja = undefined;
                    lab.bonificacionley = obj.bonificacionley;
                    lab.sueldo = obj.sueldo;
                    lab.porcentajeigss = obj.porcentajeigss;
                    lab.descuentoisr = obj.descuentoisr;
                    lab.idempresaactual = obj.idempresaactual;
                    lab.idempresadebito = obj.idempresadebito;

                    empServicios.editRow(lab, 'c_lab');
                });
            };

            $scope.addDatosPersonales = function (data, aceptar) {
                // revisar si existe o no
                let titulo = data.id ? 'Actualizacion de datos personales' : 'Creacion de datos personales';

                data.idplnempleado = $scope.emp.id > 0 ? $scope.emp.id : undefined;

                empServicios.editRow(data, 'c_per').then((r) => {
                    toaster.pop({ type: r.tipo, title: titulo, body: r.mensaje, timeout: 10000 });

                    $scope.buscar(1);

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

            // $scope.buscar({});
            $scope.$watch('ver_activos', function (newValue, oldValue) {
                $scope.buscar(newValue);
            });
        }]
    )

    .controller('ModalAlta', ['$scope', '$uibModalInstance', 'empleado', 'empresas', function
        ($scope, $uibModalInstance, empleado, empresas) {
        $scope.empleado = empleado;
        $scope.empresas = empresas;
        $scope.params = {
            idempresaactual: empleado.idempresaactual, idempresadebito: empleado.idempresadebito, sueldo: empleado.sueldo, bonificacionley: empleado.bonificacionley,
            descuentoisr: empleado.descuentoisr, porcentajeigss: empleado.porcentajeigss, fecha: moment().toDate()
        };

        $scope.ok = function () { $uibModalInstance.close($scope.params); };

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
