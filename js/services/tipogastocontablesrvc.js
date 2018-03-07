(function(){

    var tipogastocontablesrvc = angular.module('cpm.tipogastocontablesrvc', ['cpm.comunsrvc']);

    tipogastocontablesrvc.factory('tipogastocontableSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipogastocontable.php';

        var tipogastocontableSrvc = {
            lstGastoscontables: function(){
                return comunFact.doGET(urlBase + '/lstgastoscontables');
            },
            getGastocontable: function(idgstcnt){
                return comunFact.doGET(urlBase + '/getgastocontable/' + idgstcont);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipogastocontableSrvc;
    }]);

}());
