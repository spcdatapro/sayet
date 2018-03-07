(function(){

    var rptecuentaclisrvc = angular.module('cpm.rptecuentaclisrvc', ['cpm.comunsrvc']);

    rptecuentaclisrvc.factory('rptEcuentaClientesSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptecuentacli.php';

        var rptEcuentaClientesSrvc = {
            rptAntiCli: function(obj){
                return comunFact.doPOST(urlBase + '/rptecuentacli/',obj);
            }
        };
        return rptEcuentaClientesSrvc;
    }]);

}());

