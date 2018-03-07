(function(){

    var tipolocalsrvc = angular.module('cpm.tipolocalsrvc', ['cpm.comunsrvc']);

    tipolocalsrvc.factory('tipoLocalSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipolocal.php';

        var tipoLocalSrvc = {
            lstTiposLocal: function(){
                return comunFact.doGET(urlBase + '/lsttiposlocales');
            },
            getTipoLocal: function(idtipo){
                return comunFact.doGET(urlBase + '/gettipolocal/' + idtipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoLocalSrvc;
    }]);

}());