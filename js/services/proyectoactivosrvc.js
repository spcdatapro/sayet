(function(){

    var proyectoactivosrvc = angular.module('cpm.proyectoactivosrvc', ['cpm.comunsrvc']);

    proyectoactivosrvc.factory('proyectoActivoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/proyecto_activo.php';

        var proyectoActivoSrvc = {
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return proyectoActivoSrvc;
    }]);

}());
