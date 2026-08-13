(function () {

    const beneficiariosrvc = angular.module('cpm.beneficiariosrvc', ['cpm.comunsrvc']);

    beneficiariosrvc.factory('beneficiarioSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/beneficiario.php';

        const beneficiarioSrvc = {
            lstBeneficiarios: (todos) => comunFact.doGET(`${urlBase}/lstbene${todos ? ('/1') : ''}`),
            getBeneficiario: (idbene) => comunFact.doGET(`${urlBase}/getbene/${idbene}`),
            editRow: (obj, op) => comunFact.doPOST(`${urlBase}/${op}`, obj),
            agregarPermiso: function (idusuario, idempresa) {
                return comunFact.doGET(urlBase + '/ap/' + idusuario + '/' + idempresa);
            },
            getUsuarios: function (idbene) {
                return comunFact.doGET(urlBase + '/usrbene/' + idbene);
            },
            quitarPermiso: function (id) {
                return comunFact.doGET(urlBase + '/qp/' + id);
            },
            getBeneUsuario: function (idusuario) {
                return comunFact.doGET(urlBase + '/beneusr/' + idusuario);
            }
        };
        return beneficiarioSrvc;
    }]);

}());
