(function(){

    var directasrvc = angular.module('cpm.directasrvc', ['cpm.comunsrvc']);

    directasrvc.factory('directaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/directa.php';

        return {
            lstDirectas: function(idempresa){
                return comunFact.doGET(urlBase + '/lstdirectas/' + idempresa);
            },
            getDirecta: function(iddirecta){
                return comunFact.doGET(urlBase + '/getdirecta/' + iddirecta);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
