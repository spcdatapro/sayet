(function(){

    var tipofacturasrvc = angular.module('cpm.tipofacturasrvc', ['cpm.comunsrvc']);

    tipofacturasrvc.factory('tipoFacturaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipofactura.php';

        return {
            lstTiposFactura: function(){
                return comunFact.doGET(urlBase + '/lsttiposfact');
            },
            getTipoFactura: function(idtipofact){
                return comunFact.doGET(urlBase + '/gettipofact/' + idtipofact);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
