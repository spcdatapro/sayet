angular.module('cpm')
.controller('transNominaController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 
    function($scope, $http, nominaServicios, empresaSrvc){
        $scope.resultados = false;
        $scope.nomina = [];
        $scope.empresas  = [];
        $scope.shingresos = false;
        $scope.shdescuentos = false;

        $scope.buscar = function(datos) {
            $("#btnBuscar").button('loading');
            $scope.nomina = [];

            datos.fecha = datos.fch.getFullYear()+'-'+(datos.fch.getMonth()+1)+'-'+datos.fch.getDate();

        	nominaServicios.buscar(datos).then(function(data){
                if (data.exito == 1) {
                    $scope.nomina = data.resultados;
                } else {
                    alert(data.mensaje);
                }

                $("#btnBuscar").button('reset');
                
                $scope.resultados = true;
        	});
        };

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });

        $scope.showIngresos = function() {
            $scope.shdescuentos = false;
            $scope.shingresos   = true;
        }

        $scope.showDescuentos = function() {
            $scope.shingresos   = false;
            $scope.shdescuentos = true;
        }

        $scope.actualizarNomina = function(n) {
            nominaServicios.actualizarNomina(n).then(function(data){
            });
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
        
        $scope.mostrarForm = function() {
            $scope.pre = {}
            $scope.formulario = true
            $scope.hay = false
        };

        $scope.guardar = function(pre){
            if (pre.fechainicio) {
                pre.iniciopago = $scope.formatoFecha(pre.fechainicio)
            }

            if (pre.fechafin) {
                pre.liquidacion = $scope.formatoFecha(pre.fechafin)
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
            pstServicios.buscar($scope.datosbuscar).then(function(data){
                $scope.datosbuscar.inicio += parseInt(data.cantidad)
                $scope.prestamos = $scope.puestos.concat(data.resultados)
                $scope.ocultarbtn(data.cantidad, data.maximo)
                $scope.$digest()
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

        $scope.buscar({})
    }
]);