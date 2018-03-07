(function(){

    var proyectosrvc = angular.module('cpm.proyectosrvc', ['cpm.comunsrvc']);

    proyectosrvc.factory('proyectoSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/proyecto.php';

        return {
            lstProyecto: function(){
                return comunFact.doGET(urlBase + '/lstproyecto');
            },
            lstProyectosPorEmpresa: function(idempresa){
                return comunFact.doGET(urlBase + '/lstproyectoporempresa/' + idempresa);
            },
            getProyecto: function(idproyecto){
                return comunFact.doGET(urlBase + '/getproyecto/' + idproyecto);
            },
            getDetalleActivoProyecto: function(idproyecto){
                return comunFact.doGET(urlBase + '/getdetactivoproyecto/' + idproyecto);
            },
            getDetalleDocsProyecto: function(idproyecto){
                return comunFact.doGET(urlBase + '/getdetadocsproyecto/' + idproyecto);
            },
            lstUnidadesProyecto: function(idproyecto){
                return comunFact.doGET(urlBase + '/unidadesproy/' + idproyecto);
            },
            lstUnidadesDisponibles: function(idproyecto, idcontrato){
                return comunFact.doGET(urlBase + '/unidadesdisponibles/' + idproyecto + '/' + idcontrato);
            },
            getUnidad: function(idunidad){
                return comunFact.doGET(urlBase + '/unidad/' + idunidad);
            },
            getTipoUnidad: function(idtipoproyecto){
                return comunFact.doGET(urlBase + '/gettipounidad/' + idtipoproyecto);
            },
            lstServiciosUnidad: function(idunidad){
                return comunFact.doGET(urlBase + '/servuni/' + idunidad);
            },
            lstServBasicosUnidad: function(idunidad){
                return comunFact.doGET(urlBase + '/servunibasico/' + idunidad);
            },
            lstServBasicosProy: function(idproy){
                return comunFact.doGET(urlBase + '/lstsrvproy/' + idproy);
            },
            getServBasicosProy: function(idsrvproy){
                return comunFact.doGET(urlBase + '/getsrvproy/' + idsrvproy);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            rptLstProy: function(obj){
                return comunFact.doPOST(urlBase + '/rptlstproy', obj);
            },
            rptDocsVence: function(obj){
                return comunFact.doPOST(urlBase + '/rptdocsvence', obj);
            },
            getProyectoUsuarios: function(idproyecto){
                return comunFact.doGET(urlBase + '/usrproy/' + idproyecto);
            },
            getUsuariosDisponibles: function(idproyecto){
                return comunFact.doGET(urlBase + '/usrdisp/' + idproyecto);
            }
        };
    }]);

}());