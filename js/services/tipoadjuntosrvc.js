(function(){

    var tipoajuntosrvc = angular.module('cpm.tipoadjuntosrvc', ['cpm.comunsrvc']);

    tipoajuntosrvc.factory('tipoAdjuntoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipo_adjunto.php';

        var tipoAdjuntoSrvc = {
            lstTipoAdjunto: function(){
                return comunFact.doGET(urlBase + '/lsttipoadjunto');
            },
            getTipoAdjunto: function(idtipoadjunto){
                return comunFact.doGET(urlBase + '/gettipoadjunto/' + idtipoadjunto);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoAdjuntoSrvc;
    }]);

}());
