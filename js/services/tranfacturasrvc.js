(function(){

    var facturacionsrvc = angular.module('cpm.facturacionsrvc', ['cpm.comunsrvc']);

    facturacionsrvc.factory('facturacionSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/facturacion.php';

        return {
            lstCargosPendientes: function(obj){
                return comunFact.doPOST(urlBase + '/pendientes', obj);
            },
            recalcular: function(obj){
                return comunFact.doPOST(urlBase + '/recalcular', obj);
            },
            generarFacturas: function(obj){
                return comunFact.doPOST(urlBase + '/genfact', obj);
            },
            respuestaGFACE: function(obj){
                return comunFact.doPOST(urlBase + '/respuesta', obj);
            },
            lstImpresionFacturas: function(obj){
                return comunFact.doPOST(urlBase + '/lstimpfact', obj);
            }
        };
    }]);

}());
