(function(){

    var plnpagoboletoornatosrvc = angular.module('cpm.plnpagoboletoornatosrvc', ['cpm.comunsrvc']);

    plnpagoboletoornatosrvc.factory('plnPagoBoletoOrnatoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/plnpagoboletoornato.php';

        return {
            lstPagosBoleto: function(periodo, idempresa){
                return comunFact.doGET(urlBase + '/pagoboleto/' + periodo + (idempresa ? ('/' + idempresa) : ''));
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
