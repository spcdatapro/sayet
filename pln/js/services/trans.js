angular.module('cpm')
.factory('nominaServicios', ['comunFact', function(comunFact){
    var urlBase = 'pln/php/controllers/nomina.php';

    return {
        buscar: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar', obj);
        },
        buscarBono14: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar_bono14', obj);
        },
        getEmpleado: function(emp){
            return comunFact.doGET(urlBase + '/get_empleado/' + emp);
        },
        actualizarNomina: function(datos){
            return comunFact.doPOSTFiles(urlBase + '/actualizar', datos);
        }, 
        generar: function(datos) {
            return comunFact.doPOSTFiles(urlBase + '/generar', datos);
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
        terminarPlanilla: function(obj) {
            return comunFact.doPOSTFiles(urlBase + '/terminar_planilla', obj)
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
.factory('preServicios', ['comunFact', function(comunFact){
    var urlBase = 'pln/php/controllers/prestamo.php'

    return {
        buscar: function(obj){
            return comunFact.doGETJ(urlBase + '/buscar', obj)
        },
        getPuesto: function(emp){
            return comunFact.doGET(urlBase + '/get_puesto/' + emp)
        },
        guardar: function(datos){
            return comunFact.doPOSTFiles(urlBase + '/guardar', datos)
        }, 
        lista: function(obj){
            return comunFact.doGET(urlBase + '/lista')
        },
        guardarOmision: function(datos, pre){
            return comunFact.doPOSTFiles(urlBase + '/guardar_omision/' + pre, datos)
        }, 
        getOmisiones: function(pre) {
            return comunFact.doGET(urlBase + '/ver_omisiones/' + pre)
        },
        guardarAbono: function(datos, pre) {
            return comunFact.doPOSTFiles(urlBase + '/guardar_abono/' + pre, datos)
        },
        getAbonos: function(pre) {
            return comunFact.doGET(urlBase + '/ver_abonos/' + pre)
        }
    };
}]);