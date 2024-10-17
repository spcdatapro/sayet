angular.module('cpm')
.controller('MntEmpleadoController', ['$scope', '$http', 'empServicios', 'empresaSrvc', 'proyectoSrvc', 'cuentacSrvc', 'pstServicios', 'unidadSrvc', '$confirm', '$uibModal', 'planillaSrvc',
	function($scope, $http, empServicios, empresaSrvc, proyectoSrvc, cuentacSrvc, pstServicios, unidadSrvc, $confirm, $uibModal, planillaSrvc){
		$scope.formulario  = false;
		$scope.resultados  = false;
		$scope.empleados   = [];
		$scope.inicio      = 0;
		$scope.datosbuscar = [];
		$scope.buscarmas   = true;
        $scope.hay       = false;
        $scope.archivos  = [];
        $scope.empresas  = [];
        $scope.proyectos = [];
        $scope.cuentas   = [];
        $scope.puestos   = [];
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

		$scope.mostrarForm = function() {
			$scope.emp = { };
			$scope.formulario = true;
            $scope.hay = false;
            $scope.movRango = false;
            $scope.bita = {}
            $scope.movEditar = false;
		};

		$scope.guardar = function(emp, traer = false){
            // campos para bitacora
            $scope.emp.idplnmovimiento = emp.idplnmovimiento > 0 ? emp.idplnmovimiento : '11';
            $scope.emp.fechatmp = emp.fechatmp > 0 ? emp.fechatmp : moment().toDate();
            $scope.emp.fintmp = null;
            $scope.emp.movfecha = emp.movfecha > 0 ? emp.movfecha : $scope.formatoFecha(emp.fechatmp);
            $scope.emp.movobservaciones = emp.movobservaciones ? emp.movobservaciones : 'Modificacion a la ficha del empleado.';

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
			empServicios.guardar(emp).then(function(data){
				alert(data.mensaje);
                const nombre = $scope.emp.nombre;
                $scope.hay = true;
                $scope.emp = {};

                if (data.up == 0) {
                    $scope.empleados.push(data.emp);
                }

                if (traer) {
                    $scope.buscar({termino: nombre}, true);
                } else {
                    $scope.getEmpleado($scope.index);
                } 
                
                $scope.editando = false;
                // $scope.getBitacora($scope.emp.id);
			});
        };

        $scope.buscar = function(datos, traer = false) {
            $scope.formulario = false;
            console.log(datos);
        	if (Object.keys(datos).length > 0) {
        		$scope.datosbuscar = {'inicio':0, 'termino': datos.termino, 'estatus': datos.activo };
        	} else {
        		$scope.datosbuscar = {'inicio':0, 'estatus': 1};
        	}
        	empServicios.buscar($scope.datosbuscar).then(function(data){
				$scope.datosbuscar.inicio = data.cantidad;
				$scope.empleados  = data.resultados;
				$scope.resultados = true;

                $scope.ocultarbtn(data.cantidad, data.maximo);

                if (traer) {
                    $scope.getEmpleado(0);
                }
        	});
        };

        $scope.mas = function() {
        	empServicios.buscar($scope.datosbuscar).then(function(data){
        		$scope.datosbuscar.inicio += parseInt(data.cantidad);

	        	$scope.empleados = $scope.empleados.concat(data.resultados);

	    		$scope.ocultarbtn(data.cantidad, data.maximo);
        	});
        }

        $scope.ocultarbtn = function(cantidad, maximo) {
            if (parseInt(cantidad) < parseInt(maximo)) {
    	    	$scope.buscarmas = false;
    	    } else {
    	    	$scope.buscarmas = true;
    	    }
        }

        $scope.getEmpleado = function(index){
            $scope.index = index;
            $scope.emp = $scope.empleados[index];
            $scope.emp.descuentoisr = +$scope.emp.descuentoisr;
            $scope.emp.bonificacionley = parseFloat($scope.emp.bonificacionley);
            $scope.emp.sueldo = parseFloat($scope.emp.sueldo);
            $scope.emp.porcentajeigss = parseFloat($scope.emp.porcentajeigss); 
            $scope.emp.activo = parseInt($scope.emp.activo);

            if ($scope.emp.fechanacimiento) {
                $scope.emp.fchnac = $scope.formatoFechajs($scope.emp.fechanacimiento);
            }

            if ($scope.emp.ingreso) {
                $scope.emp.fching = $scope.formatoFechajs($scope.emp.ingreso);
            }

            if ($scope.emp.reingreso) {
                $scope.emp.fchrei = $scope.formatoFechajs($scope.emp.reingreso);
            }

            if ($scope.emp.baja) {
                $scope.emp.fchbaj = $scope.formatoFechajs($scope.emp.baja);
            }

            $scope.formulario = true
            $scope.hay = true
            $scope.getArchivos()
            $scope.getBitacora($scope.emp.id)
            $scope.getCatalogo()

            $scope.setUnidades($scope.emp.idproyecto);

            goTop();
        }

        $scope.nuevoMovimiento = () => {
            $scope.movRango = false;
            $scope.bita = {}
            $scope.movEditar = false;
        }

        $scope.editarMovimiento = function(index) {
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

        $scope.guardarMovimiento = function(datos) {
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

            empServicios.guardarBitacora(datos).then(function(res){
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
                planillaSrvc.anularBitacora(data).then(() => { $scope.buscar({termino: data.nombre}, true); });
            }
        }

        $scope.getBitacora = function(emp) {
            empServicios.getBitacora(emp).then(function(data){
                data[0].primero = true;
                $scope.bitacora = data;
            });
        }

        $scope.getCatalogo = () => {
            empServicios.getCatalogo().then(function(data){
                $scope.movimiento = data.movimiento
            });
        }

        $scope.agregarArchivo = function(arc) {
            if ($scope.emp.id) {
                var $btn = $("#btnAgregarArchivo").button('loading');

                arc.vence = $scope.formatoFecha(arc.fchvence);

                empServicios.agregarArchivo($scope.emp.id, arc).then(function(data){
                    $scope.getArchivos();
                    alert(data.mensaje);
                    $btn.button('reset');
                    
                });
            }
        }

        $scope.getArchivos = function(){
            if ($scope.emp.id) {
                empServicios.getArchivos($scope.emp.id).then(function(data){
                    $scope.archivos = data.archivos;
                });
            }
        }

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });

        proyectoSrvc.lstProyecto().then(function(d){
            $scope.proyectos = d;
        });

        $scope.setUnidades = function (idproyecto) {
            unidadSrvc.lstUnidadesProy(idproyecto).then(function(d){
                $scope.unidades = d;
            });
        }

        pstServicios.lista().then(function(d){
            $scope.puestos = d;
        });

        empServicios.getArchivoTipo().then(function(d) {
            $scope.archivotipo = d;
        });

        empServicios.getEmpresas().then(function(res){
            $scope.empresasPlanilla = res.empresas
        })

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate();
        };

        $scope.formatoFechajs = function(fecha) {
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
                obj.id = $scope.emp.id;
                obj.idplnmovimiento = '6';
                obj.fechatmp = moment().toDate();
                obj.fintmp = null;
                obj.movfecha = $scope.formatoFecha(obj.fechatmp);
                obj.baja = 0;
                obj.reingreso = $scope.formatoFecha(obj.fechatmp);
                obj.activo = +1;

                $scope.guardar(obj, true);
            });
        };

        /*cuentacSrvc.lstCuentasC().then(function(d){
            $scope.cuentas = d;
        });*/

        $scope.buscar({});
    }]
)

.controller('ModalAlta', ['$scope', '$uibModalInstance', 'empleado', 'empresas', function 
    ($scope, $uibModalInstance, empleado, empresas) {
    $scope.empleado = empleado;
    $scope.empresas = empresas;
    $scope.params = { idempresaactual: empleado.idempresaactual, idempresadebito: empleado.idempresadebito, sueldo: empleado.sueldo, bonificacionley: empleado.bonificacionley, 
        descuentoisr: empleado.descuentoisr, porcentajeigss: empleado.porcentajeigss
    };

    $scope.ok = function () { $uibModalInstance.close($scope.params); };

    $scope.cancel = () => { $uibModalInstance.dismiss('cancel'); };

}])
.controller('MntProsueldoController', ['$scope', '$http', 'empServicios', 'empresaSrvc', 
    function($scope, $http, empServicios, empresaSrvc){
        $scope.resultados = false;
        $scope.proyecciones = [];

        $scope.buscar = function(datos) {
        	empServicios.buscarProsueldo(datos).then(function(data){
                $scope.proyecciones = data;
                $scope.resultados = true;
        	});
        };

        $scope.actProsueldo = function(pro) {
            empServicios.guardarProsueldo(pro).then(function(data){
				console.log(data.mensaje);
			});
        };
    }
])
.controller('MntPuestoController', ['$scope', '$http', 'pstServicios',  
    function($scope, $http, pstServicios){
        $scope.formulario  = false;
		$scope.resultados  = false;
		$scope.puestos     = [];
		$scope.inicio      = 0;
		$scope.datosbuscar = [];
		$scope.buscarmas   = true;
        $scope.hay         = false;
        

		$scope.mostrarForm = function() {
			$scope.emp = {};
			$scope.formulario = true;
            $scope.hay = false;
		};

		$scope.guardar = function(emp){
			pstServicios.guardar(emp).then(function(data){
				alert(data.mensaje);
                $scope.hay = true;
                $scope.pst = {};
                
                if (data.up == 0) {
                    $scope.puestos.push(data.puesto);
                }
			});
        };

        $scope.buscar = function(datos) {
            $scope.formulario = false;

        	if (datos) {
        		$scope.datosbuscar = {'inicio':0, 'termino': datos.termino};
        	} else {
        		$scope.datosbuscar = {'inicio':0};
        	}
        	
        	pstServicios.buscar($scope.datosbuscar).then(function(data){
				$scope.datosbuscar.inicio = data.cantidad;
				$scope.puestos  = data.resultados;
                $scope.resultados = true;

				$scope.ocultarbtn(data.cantidad, data.maximo);
        	});
        };

        $scope.mas = function() {
        	pstServicios.buscar($scope.datosbuscar).then(function(data){
        		$scope.datosbuscar.inicio += parseInt(data.cantidad);

	        	$scope.puestos = $scope.puestos.concat(data.resultados);

	    		$scope.ocultarbtn(data.cantidad, data.maximo);
        	});
        }

        $scope.ocultarbtn = function(cantidad, maximo) {
        	if ( parseInt(cantidad) < parseInt(maximo) ) {
    			$scope.buscarmas = false;
    		} else {
    			$scope.buscarmas = true;
    		}
        }

        $scope.getPuesto = function(index){
             $scope.pst = $scope.puestos[index];
             $scope.formulario = true;
             $scope.hay = true;
             goTop();
        };

        $scope.buscar({});
    }
])
.controller('MntPeriodoController', ['$scope', '$http', 'periodoServicios',  
    function($scope, $http, periodoServicios){
        $scope.formulario  = false;
        $scope.resultados  = false;
        $scope.periodos    = [];
        $scope.inicio      = 0;
        $scope.datosbuscar = [];
        $scope.buscarmas   = true;
        $scope.hay         = false;
        

        $scope.mostrarForm = function() {
            $scope.prd = {};
            $scope.formulario = true;
            $scope.hay = false;
        };

        $scope.guardar = function(prd){
            prd.inicio = $scope.formatoFecha(prd.fecinicio)
            prd.fin = $scope.formatoFecha(prd.fecfin)
            
            periodoServicios.guardar(prd).then(function(data){
                alert(data.mensaje);
                $scope.hay = true;
                $scope.pst = {};
                
                if (data.up == 0) {
                    $scope.periodos.push(data.puesto);
                }
            });
        };

        $scope.buscar = function(datos) {
            $scope.formulario = false;

            if (datos) {
                $scope.datosbuscar = {'inicio':0, 'cerrado': 0};

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
                $scope.datosbuscar = {'inicio':0, 'cerrado': 1};
            }

            periodoServicios.buscar($scope.datosbuscar).then(function(data){
                $scope.datosbuscar.inicio = data.cantidad;
                $scope.periodos  = data.resultados;
                $scope.resultados = true;
                $scope.ocultarbtn(data.cantidad, data.maximo);
            });
        };

        $scope.mas = function() {
            periodoServicios.buscar($scope.datosbuscar).then(function(data){
                $scope.datosbuscar.inicio += parseInt(data.cantidad);
                $scope.periodos = $scope.periodos.concat(data.resultados);
                $scope.ocultarbtn(data.cantidad, data.maximo);
                $scope.$digest();
            });
        }

        $scope.ocultarbtn = function(cantidad, maximo) {
            if ( parseInt(cantidad) < parseInt(maximo) ) {
                $scope.buscarmas = false
            } else {
                $scope.buscarmas = true
            }
        }

        $scope.getPeriodo = function(index){
             $scope.prd = $scope.periodos[index]
             $scope.prd.fecinicio = $scope.formatoFechajs($scope.prd.inicio)
             $scope.prd.fecfin = $scope.formatoFechajs($scope.prd.fin)
             $scope.prd.cerrado = parseInt($scope.prd.cerrado)
             $scope.formulario = true
             $scope.hay = true
             goTop()
        };

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate();
        };

        $scope.formatoFechajs = function(fecha) {
            var partes = fecha.split('-');
            return new Date(partes[0], partes[1] - 1, partes[2]); 
        };

        $scope.buscar({});
    }
]);
