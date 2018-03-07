(function(){

    var solicitudotsrvc = angular.module('cpm.solicitudotsrvc', ['cpm.comunsrvc']);

    solicitudotsrvc.factory('solicitudotSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/solicitudot.php';

        var solicitudotSrvc = {
            lstSolicitudes: function(){
                return comunFact.doGET(urlBase + '/lstsolicitudes');
            },
            getSolicitud: function(idsolicitud){
                return comunFact.doGET(urlBase + '/getsolicitud/' + idsolicitud);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return solicitudotSrvc;
    }]);

}());
