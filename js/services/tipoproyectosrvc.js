(function(){

    var tipoproyectosrvc = angular.module('cpm.tipoproyectosrvc', ['cpm.comunsrvc']);

    tipoproyectosrvc.factory('tipoProyectoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipo_proyecto.php';

        var tipoProyectoSrvc = {
            lstTipoProyecto: function(){
                return comunFact.doGET(urlBase + '/lsttipoproyecto');
            },
            getTipoProyecto: function(idtipoproyecto){
                return comunFact.doGET(urlBase + '/gettipoproyecto/' + idtipoproyecto);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoProyectoSrvc;
    }]);

}());