(function(){

    var cuentacsrvc = angular.module('cpm.cuentacsrvc', ['cpm.comunsrvc']);

    cuentacsrvc.factory('cuentacSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/cuentac.php';

        var cuentacSrvc = {
            lstCuentasC: function(idempresa){
                return comunFact.doGET(urlBase + '/lstctas/' + idempresa);
            },
            getCuentaC: function(idcta){
                return comunFact.doGET(urlBase + '/getcta/' + idcta);
            },
            getByTipo: function(idempresa, tipo){
                return comunFact.doGET(urlBase + '/getbytipo/' + idempresa + '/' + tipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return cuentacSrvc;
    }]);

}());
