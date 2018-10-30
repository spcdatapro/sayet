(function(){

    var jsreportsrvc = angular.module('cpm.jsreportsrvc', []);

    jsreportsrvc.factory('jsReportSrvc', ['$http', '$sce', function($http, $sce){
        var url = window.location.origin + ':5489/api/report', props = {};
        //var url = 'http://52.35.3.1:5489/api/report', props = {};
        //var url = 'http://localhost:5489/api/report', props = {};
        var props = {}, test = false;
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
            },
            getReportText: function(shortid, obj){
                //props = {'template':{'shortid': shortid}, 'data': obj, "options": {  'Content-Type': 'text/plain;charset=windows-1252' }};
                props = {'template':{'shortid': shortid, 'contentType':'text/plain'}, 'data': obj};
                return $http.post(url, props, {responseType: 'text'}).success(function(response){return response});
            },
            saldoClientes: function(obj) {
                props = {'template': {'shortid': test ? 'HkF-ravex' : 'Hk21PKfrZ'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            saldoProveedores: function(obj){
                props = {'template':{'shortid': test ? 'BJ6TDltge' : 'SJczdKMr-'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            antiClientes: function(obj){
                props = {'template':{'shortid': test ? 'rkVpRgtex' : 'HyYyLd-B-'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            antiClientesDet: function(obj){
                props = {'template':{'shortid': test ? 'SJf2ZEkZx' : 'SkRirvMBW'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            antiProveedores: function(obj){
                props = {'template':{'shortid': test ? 'H1jsPgWZg' : 'rJTxvPfrZ'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            antiProveedoresDet: function(obj){
                props = {'template':{'shortid': test ? 'r1Z2kW-Wg' : 'HJ3gFwzBZ'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            ecuentaProveedores: function(obj){
                props = {'template':{'shortid': test ? 'HJRqDnb-e' : 'S1EmSKfrb'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            ecuentaClientes: function(obj){
				//var url = "http://localhost/sayet/php/rptecuentacli.php/rptecuentacli";
                props = {'template':{'shortid': test ? 'SJaAdNzbx' : 'H1cpYwzHZ'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
			librocompras: function(obj){
                props = {'template':{'shortid': test ? 'r10bhR6O-' : 'r10bhR6O-'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
			libroventas: function(obj){
                props = {'template':{'shortid': test ? 'BkvIVPVt-' : 'BkvIVPVt-'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
			libroisr: function(obj){
                props = {'template':{'shortid': test ? 'rkQtZHwtb' : 'rkQtZHwtb'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
			libroisrres: function(obj){
                props = {'template':{'shortid': test ? 'HJgWzrhYb' : 'HJgWzrhYb'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
			factsemitidas: function(obj){
                props = {'template':{'shortid': test ? 'BJW6LWoYb' : 'BJW6LWoYb'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            },
            printCompra: function(obj){
                props = {'template':{'shortid': test ? 'Hyh6Ta31z' : 'Hyh6Ta31z'}, 'data': obj};
                return $http.post(url, props, {responseType: 'arraybuffer'}).success(function(response){return response});
            }
        };
    }]);

}());

