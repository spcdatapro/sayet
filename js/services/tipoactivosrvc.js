(function(){

    var tipoactivosrvc = angular.module('cpm.tipoactivosrvc', ['cpm.comunsrvc']);

    tipoactivosrvc.factory('tipoactivoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipo_activo.php';

        var tipoactivoSrvc = {
            lstTipoActivo: function(){
                return comunFact.doGET(urlBase + '/lsttipoactivo');
            },
            getTipoActivo: function(idtipoactivo){
                return comunFact.doGET(urlBase + '/gettipoactivo/' + idtipoactivo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoactivoSrvc;
    }]);

}());