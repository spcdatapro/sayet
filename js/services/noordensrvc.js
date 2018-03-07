(function(){

    var noordensrvc = angular.module('cpm.noordensrvc', ['cpm.comunsrvc']);

    noordensrvc.factory('noOrdenSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/noorden.php';

        var noOrdenSrvc = {
            lstNoOrden: function(){
                return comunFact.doGET(urlBase + '/lstnoorden');
            },
            getNoOrden: function(idnoorden){
                return comunFact.doGET(urlBase + '/getnoorden/' + idnoorden);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return noOrdenSrvc;
    }]);

}());
