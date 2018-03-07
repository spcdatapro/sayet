(function(){

    var rptsaldoprovsrvc = angular.module('cpm.rptsaldoprovsrvc', ['cpm.comunsrvc']);

    rptsaldoprovsrvc.factory('rptSaldoProveedoresSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptsaldoproveedor.php';

        var rptSaldoProveedoresSrvc = {
            rptSaldoProv: function(obj){
                return comunFact.doPOST(urlBase + '/rptsaldoprov/',obj);
            }
        };
        return rptSaldoProveedoresSrvc;
    }]);

}());

