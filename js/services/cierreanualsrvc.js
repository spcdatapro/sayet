(function(){

    var cierreanualsrvc = angular.module('cpm.cierreanualsrvc', ['cpm.comunsrvc']);

    cierreanualsrvc.factory('cierreAnualSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/cierreanual.php';

        return {
            existe: function(idempresa, anio){
                return comunFact.doGET(urlBase + '/existe/' + idempresa + '/' + anio);
            },
            cierreAnual: function(obj){
                return comunFact.doPOST(urlBase + '/cierre', obj);
            }
        };
    }]);

}());
