(function(){

    const activosrvc = angular.module('cpm.activosrvc', ['cpm.comunsrvc']);

    activosrvc.factory('activoSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/activo.php';

        return {
            lstActivo: () => comunFact.doGET(`${urlBase}/lstactivo`),
            getActivo: (idactivo) => comunFact.doGET(`${urlBase}/getactivo/${idactivo}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            rptActivos: (obj) => comunFact.doPOST(`${urlBase}/rptactivos`, obj),
            rptPagosIusi: (obj) => comunFact.doPOST(`${urlBase}/rptpagosiusi`, obj),
            lstBitacora: (idactivo) => comunFact.doGET(`${urlBase}/lstbitacora/${idactivo}`),
            lstProyectosActivo: (idactivo) => comunFact.doGET(`${urlBase}/lstproyact/${idactivo}`)
        };
    }]);

}());