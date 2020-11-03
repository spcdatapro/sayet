(function(){

    const tranpagossrvc = angular.module('cpm.tranpagossrvc', ['cpm.comunsrvc']);

    tranpagossrvc.factory('tranPagosSrvc', ['comunFact', function(comunFact){
        const urlBase = 'php/pago.php';

        return {
            lstPagos: (idempresa, flimite, idmoneda) => comunFact.doGET(`${urlBase}/lstpagos/${idempresa}/${flimite}/${idmoneda}`),
            genPagos: (obj) => comunFact.doPOST(`${urlBase}/g`, obj),
            rptfactprov: (obj) => comunFact.doPOST(`${urlBase}/rptfactprov`, obj),
            rpthistpagos: (obj) => comunFact.doPOST(`${urlBase}/rpthistpagos`, obj)
        };
    }]);

}());
