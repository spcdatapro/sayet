(function(){

    const ventasrvc = angular.module('cpm.ventasrvc', ['cpm.comunsrvc']);

    ventasrvc.factory('ventaSrvc', ['comunFact', function(comunFact){
        const urlBase = 'php/venta.php';

        return {
            lstVentas: (idempresa) => comunFact.doGET(`${urlBase}/lstventas/${idempresa}`),
            lstVentasPost: (obj) => comunFact.doPOST(`${urlBase}/lstventas`, obj),
            getVenta: (idfactura) => comunFact.doGET(`${urlBase}/getventa/${idfactura}`),
            lstDetVenta: (idfactura) => comunFact.doGET(`${urlBase}/lstdetfact/${idfactura}`),
            getDetVenta: (iddetfact) => comunFact.doGET(`${urlBase}/getdetfact/${iddetfact}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            lstClientes: () => comunFact.doGET(`${urlBase}/clientes`)
        };
    }]);

}());
