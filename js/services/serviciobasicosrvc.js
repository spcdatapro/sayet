(function(){

    var serviciobasicosrvc = angular.module('cpm.serviciobasicosrvc', ['cpm.comunsrvc']);

    serviciobasicosrvc.factory('servicioBasicoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/serviciobasico.php';

        return {
            lstServiciosBasicos: function(idempresa){
                return comunFact.doGET(urlBase + '/lstservicios/' + idempresa);
            },
            getServicioBasico: function(idservicio){
                return comunFact.doGET(urlBase + '/getservicio/' + idservicio);
            },
            lstServiciosDisponibles: function(idempresa){
                return comunFact.doGET(urlBase + '/lstservdispon/' + idempresa);
            },
            lstServiciosPadre: function(){
                return comunFact.doGET(urlBase + '/lstsrvpadres');
            },
            historico: function(idservicio){
                return comunFact.doGET(urlBase + '/histo/' + idservicio);
            },
            historicoCantBase: function(idservicio){
                return comunFact.doGET(urlBase + '/histocb/' + idservicio);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());