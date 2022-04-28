(function(){

    var reciboclisrvc = angular.module('cpm.reciboclisrvc', ['cpm.comunsrvc']);

    reciboclisrvc.factory('reciboClientesSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/recibocli.php';
            //Inicio modificacion
        return {
            lstRecibosClientes: function(obj){
                return comunFact.doPOST(urlBase + '/lstreciboscli', obj);
            },
            //Fin modificaion
            getReciboCliente: function(idreccli){
                return comunFact.doGET(urlBase + '/getrecibocli/' + idreccli);
            },
            lstTranBan: function(idempresa, tipo){
                return comunFact.doGET(`${urlBase}/lsttranban/${idempresa}/${tipo}`);
            },
            lstDetRecCli: function(idreccli){
                return comunFact.doGET(urlBase + '/lstdetreccli/' + idreccli);
            },
            getDetRecCli: function(iddetrec){
                return comunFact.doGET(urlBase + '/getdetreccli/' + iddetrec);
            },
            lstDocsPend: function(idempresa, idcliente, nit, tipo){
                return comunFact.doGET(`${urlBase}/docspend/${idempresa}/${idcliente}/${nit}/${tipo}`);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            lstPagoRecCli: function(idreccli){
                return comunFact.doGET(urlBase + '/getpagorecli/' + idreccli);
            },
            lstRecPend: function(idempresa){
                return comunFact.doGET(urlBase + '/getlstrecpend/' + idempresa);
            },
            getLstRec: function(idempresa){
                return comunFact.doGET(urlBase + '/getlstrec/' + idempresa);
            },
            getPagoRec: function(idpago){
                return comunFact.doGET(urlBase + '/getpago/' + idpago);
            }
        };
 
    }]);

}());
