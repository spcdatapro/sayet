(function(){

    var pagesctrl = angular.module('cpm.pagesctrl', []);

    pagesctrl.controller('PagesController', ['$scope', '$http', '$route', '$routeParams', '$compile',
        function($scope, $http, $route, $routeParams, $compile){
            $route.current.templateUrl = 'pages/' + $routeParams.name + ".html";

            $http.get($route.current.templateUrl).then(function (msg) {
                $('#views').html($compile(msg.data)($scope));
            });
    }]);

}());
