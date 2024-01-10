(function () {

    const comprasrvc = angular.module('cpm.comprasrvc', ['cpm.comunsrvc']);

    comprasrvc.factory('compraSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/compra.php';

        return {
            lstCompras: function (idempresa) {
                return comunFact.doGET(urlBase + '/lstcomras/' + idempresa);
            },
            lstComprasFltr: function (obj) {
                return comunFact.doPOST(urlBase + '/lstcomprasfltr', obj);
            },

            getCompra: (idcompra, idot) => comunFact.doGET(`${urlBase}/getcompra/${idcompra}${+idot > 0 ? ('/' + idot) : ''}`),

            getTransPago: function (idcompra) {
                return comunFact.doGET(urlBase + '/tranpago/' + idcompra);
            },
            getCompraISR: function (idcompra) {
                return comunFact.doGET(urlBase + '/getcompisr/' + idcompra);
            },
            editRow: function (obj, op) {
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            existeCompra: function (obj) {
                return comunFact.doPOST(urlBase + '/chkexiste', obj);
            },
            buscaFactura: function (obj) {
                return comunFact.doPOST(urlBase + '/buscar', obj);
            },
            lstProyectosCompra: function (idcompra) {
                return comunFact.doGET(urlBase + '/lstproycompra/' + idcompra);
            },
            getProyectoCompra: function (idproycompra) {
                return comunFact.doGET(urlBase + '/getproycompra/' + idproycompra);
            },
            getMontoOt: (idot) => comunFact.doGET(`${urlBase}/montoots/${idot}`),
            getCheques: (idot) => comunFact.doGET(`${urlBase}/selcheques/${idot}`),
            getChequesProveedor: (obj) => comunFact.doPOST(urlBase + '/lstchq', obj),
            getOtsProveedor: (idproveedor, idempresa) => comunFact.doGET(`${urlBase}/selots/${idproveedor}/${idempresa}`),
            getFacturas: (idproveedor, idempresa) => comunFact.doGET(`${urlBase}/selfacturas/${idproveedor}/${idempresa}`),
            getDocLiquida: (idnota) => comunFact.doGET(`${urlBase}/docliquida/${idnota}`),
            getComprasIVA: (fdel, fal, cuales, idempresa) => 
            comunFact.doGET(`${urlBase}/compiva/${fdel}/${fal}/${cuales}/${idempresa}`)
        };
    }]);

}());
