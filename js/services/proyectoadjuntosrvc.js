(function(){

    var proyectoadjuntosrvc = angular.module('cpm.proyectoadjuntosrvc', ['cpm.comunsrvc']);

    proyectoadjuntosrvc.factory('proyectoAdjuntoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/proyecto_adjunto.php';

        var proyectoAdjuntoSrvc = {
            lstProyectoAdjunto: function(){
                return comunFact.doGET(urlBase + '/lstproyectoadjunto');
            },
            getProyectoAdjunto: function(idproyecto){
                return comunFact.doGET(urlBase + '/getproyectoadjunto/' + idproyecto);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return proyectoAdjuntoSrvc;
    }]);

}());
