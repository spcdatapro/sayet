(function(){

    var tipoconfigcontasrvc = angular.module('cpm.tipoconfigcontasrvc', ['cpm.comunsrvc']);

    tipoconfigcontasrvc.factory('tipoConfigContaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipoconfigconta.php';

        var tipoConfigContaSrvc = {
            lstTiposConfigConta: function(){
                return comunFact.doGET(urlBase + '/lsttipoconfigconta');
            },
            getTipoConfigConta: function(idtipo){
                return comunFact.doGET(urlBase + '/gettipoconfigconta/' + idtipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoConfigContaSrvc;
    }]);

}());
