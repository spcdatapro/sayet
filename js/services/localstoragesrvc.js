(function(){

    var localstoragesrvc = angular.module('cpm.localstoragesrvc', []);

    localstoragesrvc.factory('localStorageSrvc', [function(){
        var localStorageSrvc = {
            set: function(key, value){
                window.localStorage[key] = angular.toJson(value);
            },
            get: function(key){
                return angular.fromJson(window.localStorage[key] || null);
            },
            clear: function(key){
                window.localStorage[key] = null;
            },
            clearAll: function(){
                window.localStorage.clear();
            }
        };
        return localStorageSrvc;
    }]);

}());
