(function(){

    var bancosrvc = angular.module('cpm.bancosrvc', ['cpm.comunsrvc']);

    bancosrvc.factory('bancoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/banco.php';

        return {
            lstBancos: function(idempresa){
                return comunFact.doGET(urlBase + '/lstbcos/' + idempresa);
            },
            lstBancosActivos: function(idempresa){
                return comunFact.doGET(urlBase + '/lstbcosactivos/' + idempresa);
            },
            lstBancosFltr: function(idempresa){
                return comunFact.doGET(urlBase + '/lstbcosfltr/' + idempresa);
            },
            getBanco: function(idbanco){
                return comunFact.doGET(urlBase + '/getbco/' + idbanco);
            },
            getCorrelativoBco: function(idbanco){
                return comunFact.doGET(urlBase + '/getcorrelabco/' + idbanco);
            },
            checkTranExists: function(idbanco, tipotrans, numero){
                return comunFact.doGET(urlBase + '/chkexists/' + idbanco + '/' + tipotrans + '/' + numero);
            },
            getCuentasSumario: function(idmoneda, fdelstr, falstr){
                return comunFact.doGET(urlBase + '/ctassumario/' + idmoneda + '/' + fdelstr + '/' + falstr);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            rptEstadoCta: function(obj){
                return comunFact.doPOST(urlBase + '/rptestcta', obj);
            }
        };
    }]);

}());
