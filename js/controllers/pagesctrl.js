(function(){

    var pagesctrl = angular.module('cpm.pagesctrl', []);

    pagesctrl.controller('PagesController', ['$scope', '$http', '$route', '$routeParams', '$compile',
        function($scope, $http, $route, $routeParams, $compile){
            $route.current.templateUrl = 'pages/' + $routeParams.name + ".html";

            if (!$http.defaults.headers.get) {
                $http.defaults.headers.get = {};    
            }    
        
            // Answer edited to include suggestions from comments
            // because previous version of code introduced browser-related errors
        
            //disable IE ajax request caching
            $http.defaults.headers.get['If-Modified-Since'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            // extra
            $http.defaults.headers.get['Cache-Control'] = 'no-cache';
            $http.defaults.headers.get['Pragma'] = 'no-cache';

            $http.get($route.current.templateUrl).then(function (msg) {
                $('#views').html($compile(msg.data)($scope));
            });
    }]);

}());
