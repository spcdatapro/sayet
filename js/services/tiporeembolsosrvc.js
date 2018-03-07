(function(){

    var tiporeembolsosrvc = angular.module('cpm.tiporeembolsosrvc', ['cpm.comunsrvc']);

    tiporeembolsosrvc.factory('tipoReembolsoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tiporeembolso.php';

        var tipoReembolsoSrvc = {
            lstTiposReembolso: function(){
                return comunFact.doGET(urlBase + '/lsttiposreem');
            },
            getTipoReembolso: function(idtiporeem){
                return comunFact.doGET(urlBase + '/gettiporeem/' + idtiporeem);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoReembolsoSrvc;
    }]);

}());
