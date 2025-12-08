app.controller('repIndemnizacionController', function($scope, $http) {
    $scope.filtro = { anio: new Date().getFullYear(), idempresa: "" };
    $scope.reporte = null;

    // Ejemplo: cargar listado de empresas desde tu API
    $http.get('pln/php/controllers/empresa.php/listar').then(function(r) {
        $scope.empresas = r.data;
    });

    $scope.generarReporte = function() {
        $http.post('pln/php/controllers/nomina.php/indemnizacion', {
            anio: $scope.filtro.anio,
            idempresa: $scope.filtro.idempresa,
            agrupar: 1
        }).then(function(r) {
            $scope.reporte = r.data;
        });
    };
});
