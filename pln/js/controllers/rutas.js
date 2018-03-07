angular.module('cpm')
.controller('plnRutasController', ['$scope', '$http', '$route', '$routeParams', '$compile',
    function($scope, $http, $route, $routeParams, $compile){
        switch ($routeParams.tipo) {
            case 'mnt':
                var path = 'pln/pages/mnt/';
                break;
            case 'trans':
                var path = 'pln/pages/trans/';
                break;
            case 'rep':
                var path = 'pln/pages/rep/';
                break;

            default:
                var path = 'pln/pages/';
                break;
        }
        
        $route.current.templateUrl = path + $routeParams.pagina + ".html";

        $http.get($route.current.templateUrl).then(function (msg) {
            $('#contenidoPlanilla').html($compile(msg.data)($scope));
        });
    }
]);