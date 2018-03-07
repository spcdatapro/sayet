(function(){

    var rptlibventasrvc = angular.module('cpm.rptlibventasrvc', ['cpm.comunsrvc']);

    rptlibventasrvc.factory('rptLibroVentaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptlibroventas.php';

        var rptLibroVentaSrvc = {
            rptLibroVentas: function(idempresa, mes, anio){
                return comunFact.doGET(urlBase + '/rptlibventas/' + idempresa + '/' + mes + '/' + anio);
            }
        };
        return rptLibroVentaSrvc;
    }]);

}());
