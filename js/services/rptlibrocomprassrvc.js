(function(){

    var rptlibcompsrvc = angular.module('cpm.rptlibcompsrvc', ['cpm.comunsrvc']);

    rptlibcompsrvc.factory('rptLibroComprasSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptlibrocompras.php';

        return {
            rptLibroCompras: function(idempresa, mes, anio){
                return comunFact.doGET(urlBase + '/rptlibcomp/' + idempresa + '/' + mes + '/' + anio);
            },
            getGastosActivo: function(idempresa, mes, anio){
                return comunFact.doGET(urlBase + '/gastact/' + idempresa + '/' + mes + '/' + anio);
            }
        };

    }]);

}());
