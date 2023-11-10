angular.module('cpm')
.controller('repPlanillaController', ['$scope', '$http', 'empresaSrvc', 'empServicios',
    function($scope, $http, empresaSrvc, empServicios){
        $scope.empresas = []
        $scope.empleados = []

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({sin_limite:1}).then(function(res){
            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        })
    }
])
.controller('repReciboController', ['$scope', '$http', 'empresaSrvc', 'empServicios',
    function($scope, $http, empresaSrvc, empServicios){
        $scope.empresas = []
        $scope.empleados = []

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({sin_limite:1}).then(function(res){
            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        })
    }
])
.controller('repFiniquitoController', ['$scope', '$http', 'empresaSrvc', 'empServicios', 
    function($scope, $http, empresaSrvc, empServicios){
        $scope.empleados = []

        empServicios.buscar({'sin_limite':1}).then(function(res){
            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        });
    }
])
.controller('repEmpleadoController', ['$scope', '$http', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, empresaSrvc, empServicios, proyectoSrvc){
        $scope.empresas = []
        $scope.proyectos = []
        $scope.empresasPlanilla

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresaDebito").chosen({width:'100%'}) }, 3)
        })

        proyectoSrvc.lstProyecto().then(function(d){
            $scope.proyectos = d;
            setTimeout(function() { $("#selectProyecto").chosen({width:'100%'}) }, 3)
        })

        empServicios.getEmpresas().then(function(res){
            $scope.empresasPlanilla = res.empresas
            setTimeout(function() { $("#selectEmpresaActual").chosen({width:'100%'}) }, 3)
        })
    }
])
.controller('repProyeccionController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, nominaServicios, empresaSrvc, empServicios, proyectoSrvc){
        $scope.empresas = []
        $scope.empleados = []
        $scope.fdel = ''
        $scope.fal = ''
        $scope.params = {
            empresa: '',
            empleado: '',
            fdel: '',
            fal: '',
            bono: '1'
        }
        $scope.lista = []
        $scope.total = ''

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({sin_limite:1}).then(function(res){
            console.log(res)

            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        })

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate()
        }

        $scope.generar = () => {
            $('#proyeccion-lista').DataTable().destroy()

            $scope.lista = []
            $scope.total = ''
            $scope.params.fdel = $scope.formatoFecha($scope.fdel)
            $scope.params.fal = $scope.formatoFecha($scope.fal)

            const btn = $('#btn-generar-proyeccion').button('loading')

            nominaServicios.generarProyeccion($scope.params)
            .then(res => {
                $scope.lista = res.lista
                $scope.total = res.total
                btn.button('reset')

                setTimeout(() => {
                    $('#proyeccion-lista').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excelHtml5','csv','pdf']
                    })
                })
            });
        }
    }
])
.controller('repMovimientoController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, nominaServicios, empresaSrvc, empServicios, proyectoSrvc){
        $scope.lista = []
        $scope.tipos = []
        $scope.tipo = null
        $scope.fdel = null
        $scope.fal = null

        empServicios.getCatalogo().then(function(data){
            $scope.tipos = data.movimiento
        });

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate()
        }

        $scope.generar = () => {
            $('#movimiento-lista').DataTable().destroy()

            $scope.lista = []

            const params = {
                movdel: $scope.formatoFecha($scope.fdel),
                moval: $scope.formatoFecha($scope.fal),
                movtipo: $scope.tipo
            }

            const btn = $('#btn-generar').button('loading')

            empServicios.getMovimiento(params)
            .then(res => {
                $scope.lista = res

                btn.button('reset')

                setTimeout(() => {
                    $('#movimiento-lista').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excelHtml5','csv','pdf']
                    })
                })
            });
        }
    }
]);