(function(){

    var tipocomprasrvc = angular.module('cpm.tipocomprasrvc', ['cpm.comunsrvc']);

    tipocomprasrvc.factory('tipoCompraSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipocompra.php';

        var tipoCompraSrvc = {
            lstTiposCompra: function(){
                return comunFact.doGET(urlBase + '/lsttiposcompra');
            }
        };
        return tipoCompraSrvc;
    }]);

}());
