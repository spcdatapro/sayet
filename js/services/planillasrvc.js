(function(){

    var planillasrvc = angular.module('cpm.planillasrvc', ['cpm.comunsrvc']);

    planillasrvc.factory('planillaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/planilla.php';

        return {
            empresas: function(obj){
                return comunFact.doPOST(urlBase + '/empresas', obj);
            },
            generachq: function(obj){
                return comunFact.doPOST('php/generaplnbi.php/generachq', obj);
            },
            existe: function(fdel, fal, tipotrans, idbanco){
                return comunFact.doGET( urlBase + '/existe/' + fdel + '/' + fal + '/' + tipotrans + '/' + idbanco);
            },
            anularBitacora: function (obj) {
                return comunFact.doPOST(urlBase + '/anular_bitacora', obj);
            }
        };
    }]);

}());

