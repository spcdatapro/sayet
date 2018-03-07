(function(){

    var activoadjuntosrvc = angular.module('cpm.activoadjuntosrvc', ['cpm.comunsrvc']);

    activoadjuntosrvc.factory('activoAdjuntoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/activo_adjunto.php';

        var activoAdjuntoSrvc = {
            lstActivoAdjunto: function(){
                return comunFact.doGET(urlBase + '/lstactivoadjunto');
            },
            getActivoAdjunto: function(idactivo){
                return comunFact.doGET(urlBase + '/getactivoadjunto/' + idactivo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return activoAdjuntoSrvc;
    }]);

}());
