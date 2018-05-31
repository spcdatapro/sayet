angular.module('cpm')
.controller('repPlanillaController', ['$scope', '$http', 'empresaSrvc', 
    function($scope, $http, empresaSrvc){
        $scope.empresas = [];

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });
    }
])
.controller('repReciboController', ['$scope', '$http', 'empresaSrvc', 
    function($scope, $http, empresaSrvc){
        $scope.empresas = [];

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
        });
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
]);