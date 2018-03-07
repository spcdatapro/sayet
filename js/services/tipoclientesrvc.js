(function(){

    var tipoclientesrvc = angular.module('cpm.tipoclientesrvc', ['cpm.comunsrvc']);

    tipoclientesrvc.factory('tipoClienteSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipocliente.php';

        var tipoClienteSrvc = {
            lstTiposCliente: function(){
                return comunFact.doGET(urlBase + '/lsttiposcliente');
            },
            getTipoCliente: function(idtipocliente){
                return comunFact.doGET(urlBase + '/gettipocliente/' + idtipocliente);
            }
        };
        return tipoClienteSrvc;
    }]);

}());
