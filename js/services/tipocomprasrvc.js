(function(){

    var tipocomprasrvc = angular.module('cpm.tipocomprasrvc', ['cpm.comunsrvc']);

    tipocomprasrvc.factory('tipoCompraSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipocompra.php';

        var tipoCompraSrvc = {
            lstTiposCompra: function(){
                return comunFact.doGET(urlBase + '/lsttiposcompra');
            },
            getTipoCompra: function(idtipocomp){
                return comunFact.doGET(urlBase + '/gettipocompra/' + idtipocomp);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoCompraSrvc;
    }]);

}());
