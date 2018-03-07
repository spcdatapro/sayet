(function(){

    var tipoimpchequesrvc = angular.module('cpm.tipoimpchequesrvc', ['cpm.comunsrvc']);

    tipoimpchequesrvc.factory('tipoImpresionChequeSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipoimpcheque.php';

        return {
            lstTiposImpresionCheque: function(){
                return comunFact.doGET(urlBase + '/lsttiposimp');
            }
        };
    }]);

}());


