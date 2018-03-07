(function(){

    var facturacionaguasrvc = angular.module('cpm.facturacionaguasrvc', ['cpm.comunsrvc']);

    facturacionaguasrvc.factory('facturacionAguaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/facturaagua.php';

        return {
            lstCargosPendientes: function(obj){
                return comunFact.doPOST(urlBase + '/pendientes', obj);
            },
            recalcular: function(obj){
                return comunFact.doPOST(urlBase + '/recalcular', obj);
            },
            generarFacturas: function(obj){
                return comunFact.doPOST(urlBase + '/genfact', obj);
            }
        };
    }]);

}());

