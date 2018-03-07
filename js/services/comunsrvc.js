(function(){

    var comun = angular.module('cpm.comunsrvc', []);

    comun.factory('comunFact', ['$http', function($http){
        var comunFact = {
            doGET: function(urlBase){
                var promise = $http({method: 'GET', url: urlBase}).then(function(response){
                    return response.data;
                });
                return promise;
            },
			doGETJ: function(urlBase, obj){
                var promise = $http({
                    url: urlBase,
                    method: "GET",
                    params: obj
                 }).then(function(response){
                    return response.data;
                });
                
                return promise;
            },
            doPOST: function(urlBase, obj){
                var promise = $http({
                    url: urlBase,
                    method: 'POST',
                    data: obj
                }).then(function(response){
                    return response.data;
                });
                return promise;
            },
			doPOSTFiles: function(urlBase, obj) {
                var dform = new FormData();

                for (var i in obj) {
                    if (obj.hasOwnProperty(i)) {
                        dform.append(i, obj[i]);
                    }
                }

                var promise = $http.post(urlBase, dform, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined,'Process-Data': false}
                }).then(function(response){
                    return response.data;
                });
                return promise;
            }
        };
        return comunFact;
    }]);

}());
