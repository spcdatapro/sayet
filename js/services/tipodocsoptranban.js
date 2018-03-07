(function(){

    var tipodocsoptbsrvc = angular.module('cpm.tipodocsoptbsrvc', ['cpm.comunsrvc']);

    tipodocsoptbsrvc.factory('tipoDocSopTBSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipodocsoptranban.php';

        var tipoDocSopTBSrvc = {
            lstTiposDocTB: function(idtdmov){
                return comunFact.doGET(urlBase + '/lsttipodoc/' + idtdmov);
            }/*,
            getCuentaC: function(idcta){
                return comunFact.doGET(urlBase + '/getcta/' + idcta);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }*/
        };
        return tipoDocSopTBSrvc;
    }]);

}());
