(function(){

    var tipocambiosrvc = angular.module('cpm.tipocambiosrvc', ['cpm.comunsrvc']);

    tipocambiosrvc.factory('tipoCambioSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipocambio.php';

        var tipoCambioSrvc = {
            getTC: function(){
                return comunFact.doGET(urlBase + '/gettc');
            },
            getLastTC: function(){
                return comunFact.doGET(urlBase + '/getlasttc');
            }
        };
        return tipoCambioSrvc;
    }]);

}());

