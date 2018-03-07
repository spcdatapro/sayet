(function(){

    var reciboprovsrvc = angular.module('cpm.reciboprovsrvc', ['cpm.comunsrvc']);

    reciboprovsrvc.factory('reciboProveedoresSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/reciboprov.php';

        return {
            lstRecibosProvs: function(idempresa){
                return comunFact.doGET(urlBase + '/lstrecibosprovs/' + idempresa);
            },
            getReciboProv: function(idrecprov){
                return comunFact.doGET(urlBase + '/getreciboprov/' + idrecprov);
            },
            lstTranBan: function(idempresa){
                return comunFact.doGET(urlBase + '/lsttranban/' + idempresa);
            },
            getDetRecProv: function(idrecprov){
                return comunFact.doGET(urlBase + '/lstdetrecprov/' + idrecprov);
            },
            lstDocsPend: function(idempresa){
                return comunFact.doGET(urlBase + '/docspend/' + idempresa);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };

    }]);

}());