(function(){

    var serviciopropiosrvc = angular.module('cpm.serviciopropiosrvc', ['cpm.comunsrvc']);

    serviciopropiosrvc.factory('servicioPropioSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/serviciopropio.php';

        return {
            lstServicios: function(idempresa){
                return comunFact.doGET(urlBase + '/lstservicios/' + idempresa);
            },
            getServicio: function(idservicio){
                return comunFact.doGET(urlBase + '/getservicio/' + idservicio);
            },
            lstServiciosDisponibles: function(idempresa){
                return comunFact.doGET(urlBase + '/lstservdispon/' + idempresa);
            },
            historico: function(idservicio){
                return comunFact.doGET(urlBase + '/histo/' + idservicio);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            getLectura: function(idusuario, mes, anio, idproyecto){
                return comunFact.doGET(urlBase + '/lectura/' + idusuario + '/' + mes + '/' + anio + (idproyecto ? ('/' + idproyecto) : ''));
            },
            lstProyectosUsuario: function(idusuario){
                return comunFact.doGET(urlBase + '/proyusr/' + idusuario);
            }
        };
    }]);

}());
