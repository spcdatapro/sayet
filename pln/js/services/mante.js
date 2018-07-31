angular.module('cpm')
.factory('empServicios', ['comunFact', function(comunFact){
    var urlBase = 'pln/php/controllers/empleado.php';

    return {
        buscar: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar', obj);
        },
        getEmpleado: function(emp){
            return comunFact.doGET(urlBase + '/get_empleado/' + emp);
        },
        guardar: function(datos){
            return comunFact.doPOST(urlBase + '/guardar', datos);
        }, 
        agregarArchivo: function(emp, archivo) {
            return comunFact.doPOSTFiles(urlBase + '/agregar_archivo/' + emp, archivo);
        },
        getArchivos: function(emp) {
            return comunFact.doGET(urlBase + '/get_archivos/' + emp);
        }, 
        getArchivoTipo: function() {
            return comunFact.doGET(urlBase + '/get_archivotipo');
        },
        buscarProsueldo: function(obj) {
            return comunFact.doGETJ(urlBase + '/buscar_prosueldo', obj);
        }, 
        guardarProsueldo: function(obj) {
            return comunFact.doPOST(urlBase + '/guardar_prosueldo', obj);
        },
        getEmpresas: function(){
            return comunFact.doGET(urlBase + '/get_empresas')
        },
        getBitacora: function(emp){
            return comunFact.doGET(urlBase + '/get_bitacora/'+emp)
        }
    };
}])
.factory('pstServicios', ['comunFact', function(comunFact){
    var urlBase = 'pln/php/controllers/puesto.php';

    return {
        buscar: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar', obj);
        },
        getPuesto: function(emp){
            return comunFact.doGET(urlBase + '/get_puesto/' + emp);
        },
        guardar: function(datos){
            return comunFact.doPOST(urlBase + '/guardar', datos);
        }, 
        lista: function(obj){
            return comunFact.doGET(urlBase + '/lista');
        },
    };
}])
.factory('periodoServicios', ['comunFact', function(comunFact){
    var urlBase = 'pln/php/controllers/periodo.php';

    return {
        buscar: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar', obj);
        },
        getPuesto: function(emp){
            return comunFact.doGET(urlBase + '/get_periodo/' + emp);
        },
        guardar: function(datos){
            return comunFact.doPOST(urlBase + '/guardar', datos);
        }, 
        lista: function(obj){
            return comunFact.doGET(urlBase + '/lista');
        },
    };
}]);

