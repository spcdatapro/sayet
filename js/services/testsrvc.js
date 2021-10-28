(function(){

    var testsrvc = angular.module('cpm.testsrvc', ['cpm.comunsrvc']);

    testsrvc.factory('testSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/test.php';

        return {
            gacelaDepto: function(){
                return comunFact.doGET('http://gacela.c807.com/api.php/express/local/departamento', { 'Authorization': 'eeb82452943e85126c0450967b8a15924931db9e' });
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
