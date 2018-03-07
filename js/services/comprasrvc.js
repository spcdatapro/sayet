(function(){

    var comprasrvc = angular.module('cpm.comprasrvc', ['cpm.comunsrvc']);

    comprasrvc.factory('compraSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/compra.php';

        return {
            lstCompras: function(idempresa){
                return comunFact.doGET(urlBase + '/lstcomras/' + idempresa);
            },
            lstComprasFltr: function(obj){
                return comunFact.doPOST(urlBase + '/lstcomprasfltr', obj);
            },
            getCompra: function(idcompra){
                return comunFact.doGET(urlBase + '/getcompra/' + idcompra);
            },
            getTransPago: function(idcompra){
                return comunFact.doGET(urlBase + '/tranpago/' + idcompra);
            },
            getCompraISR: function(idcompra){
                return comunFact.doGET(urlBase + '/getcompisr/' + idcompra);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            existeCompra: function(obj){
                return comunFact.doPOST(urlBase + '/chkexiste', obj);
            },
            buscaFactura: function(obj){
                return comunFact.doPOST(urlBase + '/buscar', obj);
            },
            lstProyectosCompra: function(idcompra){
                return comunFact.doGET(urlBase + '/lstproycompra/' + idcompra);
            },
            getProyectoCompra: function(idproycompra){
                return comunFact.doGET(urlBase + '/getproycompra/' + idproycompra);
            }
        };
    }]);

}());
