(function(){

    var tipodocproysrvc = angular.module('cpm.tipodocproysrvc', ['cpm.comunsrvc']);

    tipodocproysrvc.factory('tipoDocProySrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipodocproy.php';

        var tipoDocProySrvc = {
            lstTiposDocProy: function(){
                return comunFact.doGET(urlBase + '/lsttiposdocproy');
            },
            getTipoDocProy: function(idtipodocproy){
                return comunFact.doGET(urlBase + '/gettipodocproy/' + idtipodocproy);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoDocProySrvc;
    }]);

}());