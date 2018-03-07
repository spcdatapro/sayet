(function(){

    var tiposerviciosrvc = angular.module('cpm.tiposerviciosrvc', ['cpm.comunsrvc']);

    tiposerviciosrvc.factory('tipoServicioSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tiposervicio.php';

        var tipoServicioSrvc = {
            lstTiposServicios: function(){
                return comunFact.doGET(urlBase + '/lsttiposservicios');
            },
            getTipoServicio: function(idtipo){
                return comunFact.doGET(urlBase + '/gettiposervicio/' + idtipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoServicioSrvc;
    }]);

}());

