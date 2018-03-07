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
            lstTranBan: function(idempresa){
                return comunFact.doGET(urlBase + '/lsttranban/' + idempresa);
            },
            lstDetRecCli: function(idreccli){
                return comunFact.doGET(urlBase + '/lstdetreccli/' + idreccli);
            },
            getDetRecCli: function(iddetrec){
                return comunFact.doGET(urlBase + '/getdetreccli/' + iddetrec);
            },
            lstDocsPend: function(idempresa, idcliente){
                return comunFact.doGET(urlBase + '/docspend/' + idempresa + '/' + idcliente);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
 
    }]);

}());
