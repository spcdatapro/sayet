(function(){

    var rptlibmaysrvc = angular.module('cpm.rptlibmaysrvc', ['cpm.comunsrvc']);

    rptlibmaysrvc.factory('rptLibroMayorSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptlibromayor.php';
        var rptLibroMayorSrvc = {
            rptLibroMayorEnc: function(obj){
                return comunFact.doPOST(urlBase + '/rptlibmayenc', obj);
            },
            rptLibroMayorDet: function(obj){
                return comunFact.doPOST(urlBase + '/rptlibmaydet', obj);
            }
        };
        return rptLibroMayorSrvc;
    }]);

}());

