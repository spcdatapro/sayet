(function(){

    var rptconciliabcosrvc = angular.module('cpm.rptconciliabcosrvc', ['cpm.comunsrvc']);

    rptconciliabcosrvc.factory('rptConciliaBcoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptconciliabco.php';

        return {
            rptConciliaBco: function(obj){
                return comunFact.doPOST(urlBase + '/rptconciliabco', obj);
            }
        };
    }]);

}());
