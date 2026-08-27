(function(){
    
    const rptingegrproysrvc = angular.module('cpm.gerencialsrvc', ['cpm.comunsrvc']);

    rptingegrproysrvc.factory('gerencialSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/rptgerenciales.php';

        return {
            finanzas: (obj) => comunFact.doPOST(`${urlBase}/finanzas`, obj),
            ocupacion: obj => comunFact.doPOST(`${urlBase}/ocupacion`, obj),
            ingresos: obj => comunFact.doPOST(`${urlBase}/ingresos`, obj),
            detalleFactura: (anio, idproyecto) => comunFact.doGET(`${urlBase}/detalle_factura/${anio}/${idproyecto}`),
            detalleRecibo: (anio, idproyecto) => comunFact.doGET(`${urlBase}/detalle_recibo/${anio}/${idproyecto}`)
        };
    }]);

}());
