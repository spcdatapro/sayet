(function(){

    var empresasrvc = angular.module('cpm.empresasrvc', ['cpm.comunsrvc']);

    empresasrvc.factory('empresaSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/empresa.php';

        return {
            lstEmpresas: function(){
                return comunFact.doGET(urlBase + '/lstempresas');
            },
            getEmpresa: function(idempresa){
                return comunFact.doGET(urlBase + '/getemp/' + idempresa);
            },
            lstConfigConta: function(idempresa){
                return comunFact.doGET(urlBase + '/lstconf/' + idempresa);
            },
            getConfConta: function(idconf){
                return comunFact.doGET(urlBase + '/getconf/' + idconf);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            lstEmpresasPlanilla: function(){
                return comunFact.doGET(urlBase + '/lstplnempresas');
            },
            agregarPermiso: function (idusuario, idempresa) {
                return comunFact.doGET(urlBase + '/ap/' + idusuario + '/' + idempresa);
            },
            getUsuarios: function (idempresa) {
                return comunFact.doGET(urlBase + '/usrempresa/' + idempresa);
            },
            quitarPermiso: function (id) {
                return comunFact.doGET(urlBase + '/qp/' + id);
            },
            getEmpresaUsuario: function (idusuario) {
                return comunFact.doGET(urlBase + '/empresausr/' + idusuario);
            }
        };
    }]);

}());
