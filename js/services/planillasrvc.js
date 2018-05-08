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
            }
        };
    }]);

}());

