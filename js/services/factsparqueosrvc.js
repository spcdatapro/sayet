(function(){

    var factsparqueosrvc = angular.module('cpm.factsparqueosrvc', ['cpm.comunsrvc']);

    factsparqueosrvc.factory('factsParqueoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/factsparqueo.php/';

        return {
            getFacturas: function(obj){
                return comunFact.doPOST(urlBase + 'getfacturas', obj);
            },
            insertaFacturas: function(obj){
                return comunFact.doPOST(urlBase + 'insertafacts', obj);
            }
        };
    }]);

}());