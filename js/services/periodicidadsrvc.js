(function(){

    var periodicidadsrvc = angular.module('cpm.periodicidadsrvc', ['cpm.comunsrvc']);

    periodicidadsrvc.factory('periodicidadSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/periodicidad.php';

        var periodicidadSrvc = {
            lstPeriodicidad: function(){
                return comunFact.doGET(urlBase + '/lstperiodicidad');
            }
            /*
            ,
            getTSVenta: function(idtipo){
                return comunFact.doGET(urlBase + '/gettsventa/' + idtipo);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
            */
        };
        return periodicidadSrvc;
    }]);

}());
