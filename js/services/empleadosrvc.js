(function(){

    var empleadosrvc = angular.module('cpm.empleadosrvc', ['cpm.comunsrvc']);

    empleadosrvc.factory('empleadoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/rptplnhistosueldo.php';

        return {
            lstEmpleados: function(){
                return comunFact.doGET(urlBase + '/lstempleados');
            }
        };
    }]);

}());
