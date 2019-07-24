(function(){

    const monedasrvc = angular.module('cpm.monedasrvc', ['cpm.comunsrvc']);

    monedasrvc.factory('monedaSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/moneda.php';

        return {
            lstMonedas: () => comunFact.doGET(`${urlBase}/lstmonedas`),
            getMoneda: (idmoneda) => comunFact.doGET(`${urlBase}/getmoneda/${idmoneda}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj)
        };
    }]);

}());
