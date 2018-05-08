(function(){

    var tranbacsrvc = angular.module('cpm.tranbacsrvc', ['cpm.comunsrvc']);

    tranbacsrvc.factory('tranBancSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tranbanc.php';

        return {
            lstTransacciones: function(idbanco){
                return comunFact.doGET(urlBase + '/lsttranbanc/' + idbanco);
            },
            lstTranFiltr: function(obj){
                return comunFact.doPOST(urlBase + '/lsttran', obj);
            },
            getTransaccion: function(idtran){
                return comunFact.doGET(urlBase + '/gettran/' + idtran);
            },
            lstBeneficiarios: function(){
                return comunFact.doGET(urlBase + '/lstbeneficiarios');
            },
            lstFactCompra: function(idproveedor, idtranban){
                return comunFact.doGET(urlBase + '/factcomp/' + idproveedor + '/' + idtranban);
            },
            lstReembolsos: function(idbene){
                return comunFact.doGET(urlBase + '/reem/' + idbene);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            lstDocsSoporte: function(idtran){
                return comunFact.doGET(urlBase + '/lstdocsop/' + idtran);
            },
            getDocSoporte: function(iddocsop){
                return comunFact.doGET(urlBase + '/getdocsop/' + iddocsop);
            },
            getSumDocsSop: function(idtran){
                return comunFact.doGET(urlBase + '/getsumdocssop/' + idtran);
            },
            lstCompras: function(idtran){
                return comunFact.doGET(urlBase + '/lstcompras/' + idtran);
            },
            rptcorrch: function(obj){
                return comunFact.doPOST(urlBase + '/rptcorrch', obj);
            },
            rptdocscircula: function(obj){
                return comunFact.doPOST(urlBase + '/rptdocscircula', obj);
            },
            lstCorrelativos: function(ndel,nal,idbanco){
                return comunFact.doGET(urlBase + '/correlativodelal/' + ndel + '/' + nal + '/' + idbanco);
            },
            lstAConciliar: function(idbanco, afecha, qver){
                return comunFact.doGET(urlBase + '/aconciliar/' + idbanco + '/' + afecha + '/' + qver);
            },
            imprimir: function(idtran){
                return comunFact.doGET(urlBase + '/imprimir/' + idtran);
            },
            existe: function(obj){
                return comunFact.doPOST(urlBase + '/existe', obj);
            }
        };
    }]);

}());
