(function(){

    var rptsaldoclisrvc = angular.module('cpm.rptsaldoclisrvc', ['cpm.comunsrvc']);

    rptsaldoclisrvc.factory('rptSaldoClientesSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptsaldocliente.php';

        return {
            rptSaldoCli: function(obj){
                return comunFact.doPOST(urlBase + '/rptsaldocli/',obj);
            }
        };

    }]);

}());
