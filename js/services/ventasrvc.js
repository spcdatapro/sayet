(function(){

    var ventasrvc = angular.module('cpm.ventasrvc', ['cpm.comunsrvc']);

    ventasrvc.factory('ventaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/venta.php';

        return {
            lstVentas: function(idempresa){
                return comunFact.doGET(urlBase + '/lstventas/' + idempresa);
            },
            lstVentasPost: function(obj){
                return comunFact.doPOST(urlBase + '/lstventas', obj);
            },
            getVenta: function(idfactura){
                return comunFact.doGET(urlBase + '/getventa/' + idfactura);
            },
            lstDetVenta: function(idfactura){
                return comunFact.doGET(urlBase + '/lstdetfact/' + idfactura);
            },
            getDetVenta: function(iddetfact){
                return comunFact.doGET(urlBase + '/getdetfact/' + iddetfact);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            lstClientes: function(){
                return comunFact.doGET(urlBase + '/clientes');
            }
        };
    }]);

}());
