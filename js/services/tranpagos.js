(function(){

    var tranpagossrvc = angular.module('cpm.tranpagossrvc', ['cpm.comunsrvc']);

    tranpagossrvc.factory('tranPagosSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/pago.php';

        return {
            lstPagos: function(idempresa, flimite, idmoneda){
                return comunFact.doGET(urlBase + '/lstpagos/' + idempresa + '/' + flimite + '/' + idmoneda);
            },
            genPagos: function(obj){
                return comunFact.doPOST(urlBase + '/g', obj);
            },
            rptfactprov: function (obj) {
                return comunFact.doPOST(urlBase + '/rptfactprov', obj);
            },
            rpthistpagos: function (obj) {
                return comunFact.doPOST(urlBase + '/rpthistpagos', obj);
            }
        };

    }]);

}());
