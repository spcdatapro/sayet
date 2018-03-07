(function(){

    var tipocombsrvc = angular.module('cpm.tipocombsrvc', ['cpm.comunsrvc']);

    tipocombsrvc.factory('tipoCombustibleSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipocombustible.php';

        var tipoCombustibleSrvc = {
            lstTiposCombustible: function(){
                return comunFact.doGET(urlBase + '/lsttiposcomb');
            },
            getTipoCombustible: function(idtipocomb){
                return comunFact.doGET(urlBase + '/gettipocomb/' + idtipocomb);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoCombustibleSrvc;
    }]);

}());

