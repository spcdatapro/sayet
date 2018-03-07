(function(){

    var clientesrvc = angular.module('cpm.clientesrvc', ['cpm.comunsrvc']);

    clientesrvc.factory('clienteSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/cliente.php';

        return {
            lstCliente: function(){
                return comunFact.doGET(urlBase + '/lstcliente');
            },
            getCliente: function(idcliente){
                return comunFact.doGET(urlBase + '/getcliente/' + idcliente);
            },
            rptDetContrato: function(){
                return comunFact.doGET(urlBase + '/rptdetcont');
            },
            clienteToPrint: function(idcliente){
                return comunFact.doGET(urlBase + '/clientetoprint/' + idcliente);
            },
            lstDetFact: function(idcliente){
                return comunFact.doGET(urlBase + '/lstdatosfact/' + idcliente);
            },
            getDetFact: function(iddetfact){
                return comunFact.doGET(urlBase + '/getfacturara/' + iddetfact);
            },
            lstDetServFact: function(iddetfact){
                return comunFact.doGET(urlBase + '/lstservfact/' + iddetfact);
            },
            getDetServFact: function(iddetservfact){
                return comunFact.doGET(urlBase + '/getservfact/' + iddetservfact);
            },
            lstFiadores: function(idcontrato){
                return comunFact.doGET(urlBase + '/lstdatosfia/' + idcontrato);
            },
            getFiador: function(idfiador){
                return comunFact.doGET(urlBase + '/getfiador/' + idfiador);
            },
            lstContratos: function(idcliente){
                return comunFact.doGET(urlBase + '/lstcontratos/' + idcliente);
            },
            lstContratosEmpresa: function(idcliente, idempresa){
                return comunFact.doGET(urlBase + '/lstcontemp/' + idcliente + '/' + idempresa);
            },
            getContrato: function(idcontrato){
                return comunFact.doGET(urlBase + '/getcontrato/' + idcontrato);
            },
            contratoToPrint: function(idcontrato){
                return comunFact.doGET(urlBase + '/contratotoprint/' + idcontrato);
            },
            lstDetFContrato: function(idcontrato){
                return comunFact.doGET(urlBase + '/lstdetcontrato/' + idcontrato);
            },
            getDetFContrato: function(iddetcontrato){
                return comunFact.doGET(urlBase + '/getdetcontrato/' + iddetcontrato);
            },
            chkDetFContratoFacturado: function(iddetcontrato){
                return comunFact.doGET(urlBase + '/chkdetcontfacturado/' + iddetcontrato);
            },
            chkDetFContratoAnulado: function(iddetcontrato){
                return comunFact.doGET(urlBase + '/chkcargoanulado/' + iddetcontrato);
            },
            lstCargos: function(iddetcont){
                return comunFact.doGET(urlBase + '/getcargos/' + iddetcont);
            },
            lstAdjCont: function(idcontrato){
                return comunFact.doGET(urlBase + '/lstadj/' + idcontrato);
            },
            getAdjCont: function(idadj){
                return comunFact.doGET(urlBase + '/getadj/' + idadj);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
