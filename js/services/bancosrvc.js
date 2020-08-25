(function(){

    const bancosrvc = angular.module('cpm.bancosrvc', ['cpm.comunsrvc']);

    bancosrvc.factory('bancoSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/banco.php';

        return {
            lstBancos: (idempresa) => comunFact.doGET(urlBase + '/lstbcos/' + idempresa),
            lstBancosActivos: (idempresa) => comunFact.doGET(urlBase + '/lstbcosactivos' + (idempresa ? `/${idempresa}` : '')),
            lstBancosFltr: (idempresa) => comunFact.doGET(urlBase + '/lstbcosfltr/' + idempresa),
            getBanco: (idbanco) => comunFact.doGET(urlBase + '/getbco/' + idbanco),
            getCorrelativoBco: (idbanco) => comunFact.doGET(urlBase + '/getcorrelabco/' + idbanco),
            checkTranExists: (idbanco, tipotrans, numero) => comunFact.doGET(urlBase + '/chkexists/' + idbanco + '/' + tipotrans + '/' + numero),
            getCuentasSumario: (idmoneda, fdelstr, falstr)=> comunFact.doGET(urlBase + '/ctassumario/' + idmoneda + '/' + fdelstr + '/' + falstr),
            editRow: (obj, op) => comunFact.doPOST(urlBase + '/' + op, obj),
            rptEstadoCta: (obj) => comunFact.doPOST(urlBase + '/rptestcta', obj),
            lstNombreBancosActivos: (idempresa) => comunFact.doGET(`${urlBase}/lstnombrebcosactivos` + (idempresa ? `/${idempresa}` : ''))
        };
    }]);

}());
