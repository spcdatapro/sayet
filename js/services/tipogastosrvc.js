(function(){

    var tipogastosrvc = angular.module('cpm.tipogastosrvc', ['cpm.comunsrvc']);

    tipogastosrvc.factory('tipogastoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipogasto.php';

        return {
            lstTipogastos: function(){
                return comunFact.doGET(urlBase + '/lsttipogastos');
            },
            getTipogasto: function(idtipogas){
                return comunFact.doGET(urlBase + '/gettipogasto/' + idtipogas);
            },
            lstSubTipoGasto: function(){
                return comunFact.doGET(urlBase + '/lstallsubtipo');
            },
            lstSubTipoGastoByTipoGasto: function(idtipogasto){
                return comunFact.doGET(urlBase + '/lstsubtipobytipogasto/' + idtipogasto);
            },
            getSubTipoGasto: function(idsubtipogasto){
                return comunFact.doGET(urlBase + '/getsubtipogasto/' + idsubtipogasto);
            },
            getDetSubTipo: function(idsubtipogasto){
                return comunFact.doGET(urlBase + '/lstdetcontsubtipo/' + idsubtipogasto);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());