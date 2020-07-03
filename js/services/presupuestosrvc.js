(function(){

    const presupuestosrvc = angular.module('cpm.presupuestosrvc', ['cpm.comunsrvc']);

    presupuestosrvc.factory('presupuestoSrvc', ['comunFact', function(comunFact){
        const urlBase = 'php/presupuesto.php';

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
            presupuestosPendientes: (idusr) => comunFact.doGET(`${urlBase}/lstpresupuestospend` + (!idusr ? '' : `/${idusr}`)),
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
            lstPagosOt: function(idempresa, idot){
                return comunFact.doGET(`${urlBase}/lstpagos/${idempresa}${+idot > 0 ? ('/' + idot) : ''}`);
            },
            lstPagosPendOt: function(){
                return comunFact.doGET(urlBase + '/pagospend');
            },
            lstOtsImprimir: (obj) => comunFact.doPOST(`${urlBase}/pagosgenerados`, obj),
            lstNotificaciones: function(){
                return comunFact.doGET(urlBase + '/notificaciones');
            },
            setNotificado: function(idusr){
                return comunFact.doGET(urlBase + '/setnotificado/' + idusr);
            },
            lstAmpliaciones: function(iddetpresup){
                return comunFact.doGET(urlBase + '/ampliapresup/' + iddetpresup);
            },
            getAmpliacion: function(idamplia){
                return comunFact.doGET(urlBase + '/getampliapresup/' + idamplia);
            },
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            usrApruebanOts: (id) => comunFact.doGET(`${urlBase}/usraprob` + (!id ? '' : `/${id}`))
        };
    }]);

}());

