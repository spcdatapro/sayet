angular.module('cpm')
.controller('transNominaController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 
    function($scope, $http, nominaServicios, empresaSrvc){
        $scope.resultados = false;
        $scope.nomina = [];
        $scope.empresas  = [];
        $scope.shingresos_uno = false;
        $scope.shdescuentos = false;
        $scope.primeraQuincena = false;
        $scope.shingresos_dos = false;
        $scope.edicion = true;
        
        $scope.nfecha = null
        $scope.nempresa = null

        $scope.buscar = function(datos) {
            $("#btnBuscar").button('loading');
            $scope.nomina = [];

            $scope.primeraQuincena = datos.fch.getDate() === 15 ? true : false;

            datos.fecha = datos.fch.getFullYear()+'-'+(datos.fch.getMonth()+1)+'-'+datos.fch.getDate();

            $scope.nfecha = datos.fecha
            $scope.nempresa = datos.empresa

        	nominaServicios.buscar(datos).then(function(data){
                if (data.exito == 1) {
                    $scope.nomina = data.resultados;
                } else {
                    alert(data.mensaje);
                }

                $("#btnBuscar").button('reset');
                
                $scope.resultados = true;
                $scope.showIngresos();
        	});
        };

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });

        $scope.showIngresos = function() {
            $scope.edicion = true;
            $scope.shdescuentos = false;

            if ($scope.primeraQuincena) {
                $scope.shingresos_uno = true;
                $scope.shingresos_dos = false;
            } else {
                $scope.shingresos_uno = false;
                $scope.shingresos_dos = true;
            }
        }

        $scope.showDescuentos = function() {
            $scope.edicion = true;
            $scope.shdescuentos = $scope.primeraQuincena === true ? false : true;
            $scope.shingresos_uno = false;
            $scope.shingresos_dos = false;
        }

        $scope.showTerminar = function() {
            $scope.edicion = false;
        }

        $scope.actualizarNomina = function(n) {
            nominaServicios.actualizarNomina(n).then(function(data){
            });
        }

        $scope.terminarPlanilla = function() {
            if (confirm('¿Desea continuar?')) {
                if ($scope.fecha !== null) {
                    var datos = {'fecha':$scope.nfecha}
                    if ($scope.nempresa) { datos['empresa'] = $scope.nempresa }
                    nominaServicios.terminarPlanilla(datos).then(function(res){
                        alert(res.mensaje)
                    })
                } else {
                    alert('Por favor seleccione una fecha y haga clic en buscar')
                }
            }
        }
    }
])
.controller('generarNominaController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 
    function($scope, $http, nominaServicios, empresaSrvc){
        $scope.empresas = [];

        $scope.generar = function(n) {
            $("#btnBuscar").button('loading');

            n.fecha = n.fch.getFullYear()+'-'+(n.fch.getMonth()+1)+'-'+n.fch.getDate();
            
            nominaServicios.generar(n).then(function(data){
                alert(data.mensaje);
                $("#btnBuscar").button('reset');
            });
        }

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });
    }
])
.controller('transPrestamoController', ['$scope', '$http', 'preServicios', 'empServicios',  
    function($scope, $http, preServicios, empServicios){
        $scope.formulario  = false
        $scope.resultados  = false
        $scope.prestamos   = []
        $scope.inicio      = 0
        $scope.datosbuscar = []
        $scope.buscarmas   = true
        $scope.hay         = false
        $scope.empleados   = []
        $scope.omisiones   = []
        $scope.abonos      = []
        
        $scope.mostrarForm = function() {
            $scope.pre = {}
            $scope.formulario = true
            $scope.hay = false
        };

        $scope.guardar = function(pre){
            if (pre.fechainicio) {
                pre.iniciopago = $scope.formatoFecha(pre.fechainicio)
            } else {
                pre.iniciopago = 0;
            }

            if (pre.fechafin) {
                pre.liquidacion = $scope.formatoFecha(pre.fechafin)
            } else {
                pre.liquidacion = 0;
            }

            preServicios.guardar(pre).then(function(data){
                alert(data.mensaje)
                $scope.hay = true
                $scope.pre = {}
                
                if (data.up == 0) {
                    $scope.prestamos.push(data.prestamo)
                }
            });
        };

        $scope.buscar = function(datos) {
            $scope.formulario = false

            if (datos) {
                $scope.datosbuscar = {'inicio':0, 'termino': datos.termino}
            } else {
                $scope.datosbuscar = {'inicio':0}
            }
            
            preServicios.buscar($scope.datosbuscar).then(function(data){
                $scope.datosbuscar.inicio = data.cantidad
                $scope.prestamos  = data.resultados
                $scope.resultados = true
                $scope.ocultarbtn(data.cantidad, data.maximo)
            })
        }

        $scope.mas = function() {
            preServicios.buscar($scope.datosbuscar).then(function(data){
                $scope.datosbuscar.inicio += parseInt(data.cantidad)
                $scope.prestamos = $scope.prestamos.concat(data.resultados)
                $scope.ocultarbtn(data.cantidad, data.maximo)
            })
        }

        $scope.ocultarbtn = function(cantidad, maximo) {
            if ( parseInt(cantidad) < parseInt(maximo) ) {
                $scope.buscarmas = false
            } else {
                $scope.buscarmas = true
            }
        }

        $scope.getPrestamo = function(index){
             $scope.pre = $scope.prestamos[index]

             if ($scope.pre.iniciopago) {
                $scope.pre.fechainicio = $scope.formatoFechajs($scope.pre.iniciopago)
             }

             if ($scope.pre.liquidacion) {
                $scope.pre.fechafin = $scope.formatoFechajs($scope.pre.liquidacion)
             }
             
             $scope.pre.monto = parseFloat($scope.pre.monto)
             $scope.pre.cuotamensual = parseFloat($scope.pre.cuotamensual)
             $scope.verOmisiones($scope.pre.id)
             $scope.formulario = true
             $scope.hay = true
             goTop()
        }

        empServicios.buscar({'sin_limite':1}).then(function(d) {
            $scope.empleados = d.resultados
        })

        $scope.verOmisiones = function(pre) {
            preServicios.getOmisiones(pre).then(function(d){
                $scope.omisiones = d.omisiones
            })
        }

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate()
        }

        $scope.formatoFechajs = function(fecha) {
            var partes = fecha.split('-');
            return new Date(partes[0], partes[1] - 1, partes[2])
        }

        $scope.guardarOmision = function(omi) {
            if (omi && omi.fecha_omision) {
                $("#btnGuardarOmision").button('loading')
                omi.fecha = $scope.formatoFecha(omi.fecha_omision)
                preServicios.guardarOmision(omi, $scope.pre.id).then(function(data){
                    $scope.verOmisiones($scope.pre.id)
                    alert(data.mensaje)
                    $("#btnGuardarOmision").button('reset')
                    $('#myModal').modal('hide')
                });
            } else {
                alert('Por favor ingrese una fecha válida.')
            }
        }

        $scope.guardarAbono = function(ab) {
            if (ab.fecha_abono && ab.monto && ab.concepto) {
                $('#btnGuardarAbono').button('loading')
                ab.fecha = $scope.formatoFecha(ab.fecha_abono)
                preServicios.guardarAbono(ab, $scope.pre.id).then(function(data){
                    $scope.verAbonos($scope.pre.id)
                    alert(data.mensaje)
                    $('#btnGuardarAbono').button('reset')
                    $('#mdlAbono').modal('hide')
                    $scope.abono = {}
                })
            } else {
                alert('Por favor llene el formulario. Todos los campos son obligatorios.')
            }
        }

        $scope.verAbonos = function(pre) {
            preServicios.getAbonos(pre).then(function(d){
                $scope.abonos = d.abonos
            })
        }

        $scope.buscar({})
    }
])
.controller('transBono14Controller', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 
    function($scope, $http, nominaServicios, empresaSrvc){
        $scope.resultados = false;
        $scope.nomina = [];
        $scope.empresas  = [];
        $scope.shingresos_uno = false;
        $scope.shdescuentos = false;
        $scope.primeraQuincena = false;
        $scope.shingresos_dos = false;
        $scope.edicion = true;
        
        $scope.nfecha = null
        $scope.nempresa = null

        $scope.buscar = function(datos) {
            $("#btnBuscar").button('loading');
            $scope.nomina = [];

            $scope.primeraQuincena = datos.fch.getDate() === 15 ? true : false;

            datos.fecha = datos.fch.getFullYear()+'-'+(datos.fch.getMonth()+1)+'-'+datos.fch.getDate();

            $scope.nfecha = datos.fecha
            $scope.nempresa = datos.empresa

            nominaServicios.buscarBono14(datos).then(function(data){
                if (data.exito == 1) {
                    $scope.nomina = data.resultados;
                } else {
                    alert(data.mensaje);
                }

                $("#btnBuscar").button('reset');
                
                $scope.resultados = true;
                $scope.showIngresos();
            });
        };

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });

        $scope.showIngresos = function() {
            $scope.edicion = true;
            $scope.shdescuentos = false;

            if ($scope.primeraQuincena) {
                $scope.shingresos_uno = true;
                $scope.shingresos_dos = false;
            } else {
                $scope.shingresos_uno = false;
                $scope.shingresos_dos = true;
            }
        }

        $scope.showDescuentos = function() {
            $scope.edicion = true;
            $scope.shdescuentos = $scope.primeraQuincena === true ? false : true;
            $scope.shingresos_uno = false;
            $scope.shingresos_dos = false;
        }

        $scope.showTerminar = function() {
            $scope.edicion = false;
        }

        $scope.actualizarNomina = function(n) {
            nominaServicios.actualizarNomina(n).then(function(data){
            });
        }

        $scope.terminarPlanilla = function() {
            if (confirm('¿Desea continuar?')) {
                if ($scope.fecha !== null) {
                    var datos = {'fecha':$scope.nfecha}
                    if ($scope.nempresa) { datos['empresa'] = $scope.nempresa }
                    nominaServicios.terminarPlanilla(datos).then(function(res){
                        alert(res.mensaje)
                    })
                } else {
                    alert('Por favor seleccione una fecha y haga clic en buscar')
                }
            }
        }
    }
]);