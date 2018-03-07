(function(){

    var reembolsosrvc = angular.module('cpm.reembolsosrvc', ['cpm.comunsrvc']);

    reembolsosrvc.factory('reembolsoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/reembolso.php';

        return {
            lstReembolsos: function(idempresa){
                return comunFact.doGET(urlBase + '/lstreembolsos/' + idempresa);
            },
            getReembolso: function(idreembolso){
                return comunFact.doGET(urlBase + '/getreembolso/' + idreembolso);
            },
            lstBeneficiarios: function(){
                return comunFact.doGET(urlBase + '/lstbenef');
            },
            lstCompras: function(idreembolso){
                return comunFact.doGET(urlBase + '/getdet/' + idreembolso);
            },
            getCompra: function(idcompra){
                return comunFact.doGET(urlBase + '/getcomp/' + idcompra);
            },
            getTranBan: function(idreembolso){
                return comunFact.doGET(urlBase + '/tranban/' + idreembolso);
            },
            toPrint: function(idreembolso){
                return comunFact.doGET(urlBase + '/toprint/' + idreembolso);
            },
            calculaISR: function(obj){
                return comunFact.doPOST(urlBase + '/calcisr', obj);
            },
            setRevisada: function(idcompra){
                return comunFact.doGET(urlBase + '/setrevisada/' + idcompra);
            },
            reaperturar: function(obj){
                return comunFact.doPOST(urlBase + '/reapertura', obj);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());

