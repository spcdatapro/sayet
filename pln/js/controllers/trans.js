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
]);