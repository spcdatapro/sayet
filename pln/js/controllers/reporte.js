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
]);