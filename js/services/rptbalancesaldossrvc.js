(function(){

    var rptbalsalsrvc = angular.module('cpm.rptbalsalsrvc', ['cpm.comunsrvc']);

    rptbalsalsrvc.factory('rptBalanceSaldosSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptbalancesaldos.php';

        var rptBalanceSaldosSrvc = {
            rptBalSal: function(obj){
                return comunFact.doPOST(urlBase + '/rptbalsal', obj);
            }
        };
        return rptBalanceSaldosSrvc;
    }]);

}());
