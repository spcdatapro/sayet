(function(){

    const proveedorsrvc = angular.module('cpm.proveedorsrvc', ['cpm.comunsrvc']);

    proveedorsrvc.factory('proveedorSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/proveedor.php';

        return {
            lstProveedores: (todos) => comunFact.doGET(`${urlBase}/lstprovs${todos ? ('/1'): ''}`),
            getProveedor: (idprov) => comunFact.doGET(`${urlBase}/getprov/${idprov}`),
            getProveedorByNit: (nit) => comunFact.doGET(`${urlBase}/getprovbynit/${nit}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            lstDetCuentaC: (idprov) => comunFact.doGET(`${urlBase}/detcontprov/${idprov}`),
            getDetCuentaC: (iddet) => comunFact.doGET(`${urlBase}/getdetcontprov/${iddet}`),
            getLstCuentasCont: (idprov, idempresa) => comunFact.doGET(`${urlBase}/lstdetcontprov/${idprov}/${idempresa}`),
            lstCuentacProv: (idprov, idempresa) => comunFact.doGET(`${urlBase}/lstdetcontprovifnull/${idprov}/${idempresa}`)
        };
    }]);

}());
