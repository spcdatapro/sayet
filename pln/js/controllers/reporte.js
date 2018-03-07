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
]);