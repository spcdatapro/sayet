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
            lstPagosOt: function(idempresa){
                return comunFact.doGET(`${urlBase}/lstpagos/${idempresa}`);
            },
            lstPagosPendOt: () => comunFact.doGET(urlBase + '/pagospend'),
            lstPagosPendOtContado: () => comunFact.doGET(urlBase + '/pagopencont'),
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
            ampliacionPresup: function(){
                return comunFact.doGET(urlBase + '/aprobacionamp');
            },
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            usrApruebanOts: (id) => comunFact.doGET(`${urlBase}/usraprob` + (!id ? '' : `/${id}`)),
            lstOtsAdjuntos: (idot, multiple) => comunFact.doGET(`${urlBase}/lstotadjuntos/${idot}/${multiple}`),
            lstOTMs: () => comunFact.doGET(`${urlBase}/lstotm`),
            pagoOt: function(idempresa){
                return comunFact.doGET(`${urlBase}/pagoot/${idempresa}`);
            },
        };
    }]);

}());

