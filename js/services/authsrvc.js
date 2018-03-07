(function(){

    var authsrvc = angular.module('cpm.authsrvc', ['cpm.comunsrvc']);

    authsrvc.factory('authSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/usr.php';

        return {
            doLogin: function(objAuth){
                return comunFact.doPOST(urlBase + '/auth', objAuth);
            },
            getMenu: function(usrId){
                return comunFact.doGET(urlBase + '/menu/' + usrId);
            },
            lstPerfiles: function(){
                return comunFact.doGET(urlBase + '/lstperfiles');
            },
            getPerfil: function(usrId){
                return comunFact.doGET(urlBase + '/perfil/' + usrId);
            },
            addPerfil: function(obj){
                return comunFact.doPOST(urlBase + '/c', obj);
            },
            updPerfil: function(obj){
                return comunFact.doPOST(urlBase + '/updperf', obj);
            },
            getPermisos: function(usrId){
                return comunFact.doGET(urlBase + '/permisos/' + usrId);
            },
            setPermiso: function(obj){
                return comunFact.doPOST(urlBase + '/setperm', obj);
            },
            getSession: function(){
                return comunFact.doGET(urlBase + '/getsess');
            },
            setEmpresaSess: function(idEmpresa){
                return comunFact.doGET(urlBase + '/setempresess/' + idEmpresa);
            },
            getUltimaEmpresa: function(idusuario){
                return comunFact.doGET(urlBase + '/getultempre/' + idusuario);
            },
            setUltimaEmpresa: function(idusuario, idEmpresa){
                return comunFact.doGET(urlBase + '/setultempre/' + idusuario + '/' + idEmpresa);
            },
            doLogOut: function(){
                return comunFact.doGET(urlBase + '/logout');
            },
            gpr: function(obj){
                return comunFact.doPOST(urlBase + '/getPermisosRoute', obj).then(function(d){
                    var p = {a: false, c: false, m: false, e:false };
                    p.a = parseInt(d[0].accesar) == 1;
                    p.c = parseInt(d[0].crear) == 1;
                    p.m = parseInt(d[0].modificar) == 1;
                    p.e = parseInt(d[0].eliminar) == 1;
                    return p;
                });
            },
            getPermiso: function(idusuario, ruta, permiso){
                return comunFact.doGET(urlBase + '/getpermiso/' + idusuario + '/' + ruta + '/' + permiso);
            }
        };
    }]);

}());
