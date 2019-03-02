(function(){

    var directasrvc = angular.module('cpm.directasrvc', ['cpm.comunsrvc']);

    directasrvc.factory('directaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/directa.php';

        return {
            lstDirectas: function(obj){
                return comunFact.doPOST(urlBase + '/lstdirectas', obj);
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
