(function(){

    var rptbalgensrvc = angular.module('cpm.rptbalgensrvc', ['cpm.comunsrvc']);

    rptbalgensrvc.factory('rptBalanceGeneralSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptbalancegeneral.php';

        var rptBalanceGeneralSrvc = {
            rptBalGen: function(obj){
                return comunFact.doPOST(urlBase + '/rptbalgen', obj);
            }
        };
        return rptBalanceGeneralSrvc;
    }]);

}());

