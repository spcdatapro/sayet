(function(){

    var tipoipcsrvc = angular.module('cpm.tipoipcsrvc', ['cpm.comunsrvc']);

    tipoipcsrvc.factory('tipoIpcSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipoipc.php';

        return {
            lstTipoIpc: function(){
                return comunFact.doGET(urlBase + '/lsttipoipc');
            },
            getTipoIpc: function(idtipoipc){
                return comunFact.doGET(urlBase + '/gettipoipc/' + idtipoipc);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
