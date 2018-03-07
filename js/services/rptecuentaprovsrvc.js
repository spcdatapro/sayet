(function(){

    var rptecuentaprovsrvc = angular.module('cpm.rptecuentaprovsrvc', ['cpm.comunsrvc']);

    rptecuentaprovsrvc.factory('rptEcuentaProveedoresSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptecuentaprov.php';

        var rptEcuentaProveedoresSrvc = {
            rptAntiProv: function(obj){
                return comunFact.doPOST(urlBase + '/rptecuentaprov/',obj);
            }
        };
        return rptEcuentaProveedoresSrvc;
    }]);

}());
