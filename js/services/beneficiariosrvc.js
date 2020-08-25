(function(){

    const beneficiariosrvc = angular.module('cpm.beneficiariosrvc', ['cpm.comunsrvc']);

    beneficiariosrvc.factory('beneficiarioSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/beneficiario.php';

        const beneficiarioSrvc = {
            lstBeneficiarios: (todos) => comunFact.doGET(`${urlBase}/lstbene${todos ? ('/1'): ''}`),
            getBeneficiario: (idbene) => comunFact.doGET(`${urlBase}/getbene/${idbene}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj)
        };
        return beneficiarioSrvc;
    }]);

}());
