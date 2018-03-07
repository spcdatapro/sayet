(function(){

    var municipiosrvc = angular.module('cpm.municipiosrvc', ['cpm.comunsrvc']);

    municipiosrvc.factory('municipioSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/municipio.php';

        var municipioSrvc = {
            lstAllMunicipios: function(){
                return comunFact.doGET(urlBase + '/lstallmunicipios');
            },
            lstMunicipios: function(){
                return comunFact.doGET(urlBase + '/lstmunicipios');
            },
            getMunicipio: function(idmunicipio){
                return comunFact.doGET(urlBase + '/getmunicipio/' + idmunicipio);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return municipioSrvc;
    }]);

}());
