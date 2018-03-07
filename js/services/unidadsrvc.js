(function(){

    var unidadsrvc = angular.module('cpm.unidadsrvc', ['cpm.comunsrvc']);

    unidadsrvc.factory('unidadSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/unidad.php';

        var unidadSrvc = {
            lstUnidades: function(){
                return comunFact.doGET(urlBase + '/lstunidades');
            },
            getUnidad: function(idunidad){
                return comunFact.doGET(urlBase + '/getunidad/' + idunidad);
            },
            lstContadores: function(idunidad){
                return comunFact.doGET(urlBase + '/contadores/' + idunidad);
            },
            getContador: function(idcont){
                return comunFact.doGET(urlBase + '/getcontador/' + idcont);
            },
            lstServicios: function(idunidad){
                return comunFact.doGET(urlBase + '/servicios/' + idunidad);
            },
            getServicio: function(idservicio){
                return comunFact.doGET(urlBase + '/getservicio/' + idservicio);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return unidadSrvc;
    }]);

}());
