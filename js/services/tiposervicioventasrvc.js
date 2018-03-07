(function(){

    var tiposervicioventasrvc = angular.module('cpm.tiposervicioventasrvc', ['cpm.comunsrvc']);

    tiposervicioventasrvc.factory('tipoServicioVentaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tiposervicioventa.php';

        return {
            lstTSVenta: function(){
                return comunFact.doGET(urlBase + '/lsttsventa');
            },
            getTSVenta: function(idtipo){
                return comunFact.doGET(urlBase + '/gettsventa/' + idtipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
