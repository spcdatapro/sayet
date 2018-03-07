(function(){

    var rptanticlisrvc = angular.module('cpm.rptanticlisrvc', ['cpm.comunsrvc']);

    rptanticlisrvc.factory('rptAntiClientesSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptanticliente.php';

        var rptAntiClientesSrvc = {
            rptAntiCli: function(obj){
                return comunFact.doPOST(urlBase + '/rptanticli/',obj);
            }
        };
        return rptAntiClientesSrvc;
    }]);

}());
