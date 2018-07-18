(function(){

    var activosrvc = angular.module('cpm.activosrvc', ['cpm.comunsrvc']);

    activosrvc.factory('activoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/activo.php';

        return {
            lstActivo: function(){
                return comunFact.doGET(urlBase + '/lstactivo');
            },
            getActivo: function(idactivo){
                return comunFact.doGET(urlBase + '/getactivo/' + idactivo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            rptActivos: function(obj){
                return comunFact.doPOST(urlBase + '/rptactivos', obj);
            },
            rptPagosIusi: function(obj){
                return comunFact.doPOST(urlBase + '/rptpagosiusi', obj);
            },
            lstBitacora: function(idactivo){
                return comunFact.doGET(urlBase + '/lstbitacora/' + idactivo);
            },
            lstProyectosActivo: function(idactivo){
                return comunFact.doGET(urlBase + '/lstproyact/' + idactivo);
            }
        };
    }]);

}());