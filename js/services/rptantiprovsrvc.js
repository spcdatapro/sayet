(function(){

    var rptantiprovsrvc = angular.module('cpm.rptantiprovsrvc', ['cpm.comunsrvc']);

    rptantiprovsrvc.factory('rptAntiProveedoresSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptantiproveedor.php';

        var rptAntiProveedoresSrvc = {
            rptAntiProv: function(obj){
                return comunFact.doPOST(urlBase + '/rptantiprov/',obj);
            }
        };
        return rptAntiProveedoresSrvc;
    }]);

}());
