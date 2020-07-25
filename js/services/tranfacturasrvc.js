(function(){

    const facturacionsrvc = angular.module('cpm.facturacionsrvc', ['cpm.comunsrvc']);

    facturacionsrvc.factory('facturacionSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/facturacion.php';

        return {
            lstCargosPendientes: (obj) => comunFact.doPOST(urlBase + '/pendientes', obj),
            lstCargosPendientesFEL: (obj) => comunFact.doPOST(urlBase + '/pendientesfel', obj),
            detalleCargosPendientesFEL: (obj) => comunFact.doPOST(urlBase + '/detpendientefel', obj),
            recalcular: (obj) => comunFact.doPOST(urlBase + '/recalcular', obj),
            recalcularFEL: (obj) => comunFact.doPOST(urlBase + '/recalcularfel', obj),
            generarFacturas: (obj) => comunFact.doPOST(urlBase + '/genfact', obj),
            generarFacturasFEL: (obj) => comunFact.doPOST(urlBase + '/genfactfel', obj),
            respuestaGFACE: (obj) => comunFact.doPOST(urlBase + '/respuesta', obj),
            lstImpresionFacturas: (obj) => comunFact.doPOST(urlBase + '/lstimpfact', obj),
            printFacturas: (obj) => comunFact.doPOST(`${urlBase}/prntfact`, obj),
            factsPendGface: (obj) => comunFact.doPOST(`${urlBase}/gengface`, obj),
            factsPendFEL: (obj) => comunFact.doPOST(`${urlBase}/genfel`, obj)
        };
    }]);

}());
