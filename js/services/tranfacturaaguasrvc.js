(function(){

    const facturacionaguasrvc = angular.module('cpm.facturacionaguasrvc', ['cpm.comunsrvc']);

    facturacionaguasrvc.factory('facturacionAguaSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/facturaagua.php';

        return {
            lstCargosPendientes: (obj) => comunFact.doPOST(urlBase + '/pendientes', obj),
            lstCargosPendientesFEL: (obj) => comunFact.doPOST(urlBase + '/pendientesfel', obj),
            lstCargosPendientesFELRevision: (obj) => comunFact.doPOST(urlBase + '/pendientesfelrevision', obj),
            recalcular: (obj) => comunFact.doPOST(urlBase + '/recalcular', obj),
            generarFacturas: (obj) => comunFact.doPOST(urlBase + '/genfact', obj),
            generarFacturasFEL: (obj) => comunFact.doPOST(urlBase + '/genfactfel', obj)
        };
    }]);

}());

