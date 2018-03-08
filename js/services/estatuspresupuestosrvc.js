(function(){

    var estatuspresupuestosrvc = angular.module('cpm.estatuspresupuestosrvc', ['cpm.comunsrvc']);

    estatuspresupuestosrvc.factory('estatusPresupuestoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/estatuspresupuesto.php';

        return {
            lstEstatusPresupuesto: function(){
                return comunFact.doGET(urlBase + '/lstestpres');
            }
        };
    }]);

}());

