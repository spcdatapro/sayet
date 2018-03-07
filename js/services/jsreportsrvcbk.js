(function(){

    var jsreportsrvc = angular.module('cpm.jsreportsrvc', []);

    jsreportsrvc.factory('jsReportSrvc', ['$http', '$sce', function($http, $sce){
        var url = window.location.origin + ':5489/api/report', props = {};
        //var url = 'http://localhost:5489/api/report', props = {};
        return {
            getReport: function(shortid, obj){
                props = {'template':{'shortid': shortid}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            getPDFReport: function(shortid, obj){
                props = { 'template':{'shortid': shortid}, 'data': obj };
                return $http.post(url, props, {responseType: 'arraybuffer'}).then(function(response){
                    var file = new Blob([response.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    return $sce.trustAsResourceUrl(fileURL);
                });
            }
        };
    }]);

}());