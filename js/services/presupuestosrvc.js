(function(){

    var presupuestosrvc = angular.module('cpm.presupuestosrvc', ['cpm.comunsrvc']);

    presupuestosrvc.factory('presupuestoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/presupuesto.php';

        return {
            lstPresupuestos: function(obj){
                return comunFact.doPOST(urlBase + '/lstpresupuestos', obj);
            },
            getPresupuesto: function(idpresupuesto){
                return comunFact.doGET(urlBase + '/getpresupuesto/' + idpresupuesto);
            },
            lstOts: function(idpresupuesto){
                return comunFact.doGET(urlBase + '/lstot/' + idpresupuesto);
            },
            getOt: function(idot){
                return comunFact.doGET(urlBase + '/getot/' + idot);
            },
            presupuestosPendientes: function(){
                return comunFact.doGET(urlBase + '/lstpresupuestospend');
            },
            presupuestosAprobados: function(obj){
                return comunFact.doPOST(urlBase + '/lstpresaprob', obj);
            },
            notasPresupuesto: function(idot){
                return comunFact.doGET(urlBase + '/lstnotas/' + idot);
            },
            getAvanceOt: function(idot){
                return comunFact.doGET(urlBase + '/avanceot/' + idot);
            },
            lstDetPagoOt: function(idot){
                return comunFact.doGET(urlBase + '/lstdetpago/' + idot);
            },
            getDetPagoOt: function(iddetpago){
                return comunFact.doGET(urlBase + '/getdetpago/' + iddetpago);
            },
            lstPagosOt: function(idempresa){
                return comunFact.doGET(urlBase + '/lstpagos/' + idempresa);
            },
            lstNotificaciones: function(){
                return comunFact.doGET(urlBase + '/notificaciones');
            },
            setNotificado: function(idusr){
                return comunFact.doGET(urlBase + '/setnotificado/' + idusr);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());

