(function(){

    var rptestressrvc = angular.module('cpm.rptestressrvc', ['cpm.comunsrvc']);

    rptestressrvc.factory('rptEstadoResultadosSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptestadoresultados.php';

        var rptEstadoResultadosSrvc = {
            rptEstRes: function(obj){
                return comunFact.doPOST(urlBase + '/rptestres', obj);
            }
        };
        return rptEstadoResultadosSrvc;
    }]);

}());
