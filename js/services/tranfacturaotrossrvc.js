(function(){

    var facturaotrossrvc = angular.module('cpm.facturaotrossrvc', ['cpm.comunsrvc']);

    facturaotrossrvc.factory('facturaOtrosSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/facturaotros.php';

        return {
            lstFacturas: function(idempresa, cuales){
                return comunFact.doGET(urlBase + '/lstfacturas/' + idempresa + '/' + cuales);
            },
            getFactura: function(idfactura){
                return comunFact.doGET(urlBase + '/getfactura/' + idfactura);
            },
            lstDetFactura: function(idfactura){
                return comunFact.doGET(urlBase + '/lstdetfact/' + idfactura);
            },
            getDetFactura: function(iddetfact){
                return comunFact.doGET(urlBase + '/getdetfact/' + iddetfact);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
