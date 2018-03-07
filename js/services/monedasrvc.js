(function(){

    var monedasrvc = angular.module('cpm.monedasrvc', ['cpm.comunsrvc']);

    monedasrvc.factory('monedaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/moneda.php';

        return {
            lstMonedas: function(){
                return comunFact.doGET(urlBase + '/lstmonedas');
            },
            getMoneda: function(idmoneda){
                return comunFact.doGET(urlBase + '/getmoneda/' + idmoneda);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
