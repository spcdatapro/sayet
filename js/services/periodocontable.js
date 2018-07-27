(function(){

    var pcontsrvc = angular.module('cpm.pcontsrvc', ['cpm.comunsrvc']);

    pcontsrvc.factory('periodoContableSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/periodocontable.php';

        return {
            lstPeriodosCont: function(){
                return comunFact.doGET(urlBase + '/lstpcont');
            },
            getPeriodoCont: function(idpcont){
                return comunFact.doGET(urlBase + '/getpcont/' + idpcont);
            },
            validaFecha: function(fecha){
                return comunFact.doPOST(urlBase + '/validar', {fecha: fecha});
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
    }]);

}());
