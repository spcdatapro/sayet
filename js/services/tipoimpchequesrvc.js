(function(){

    const tipoimpchequesrvc = angular.module('cpm.tipoimpchequesrvc', ['cpm.comunsrvc']);

    tipoimpchequesrvc.factory('tipoImpresionChequeSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/tipoimpcheque.php';

        return {
            lstTiposImpresionCheque: () => comunFact.doGET(`${urlBase}/lsttiposimp`),
            lstCampos: (formato) => comunFact.doGET(`${urlBase}/lstcampos/${formato}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj)
        };
    }]);

}());


