angular.module('cpm')
.factory('empServicios', ['comunFact', '$http', '$sce', function(comunFact, $http, $sce){
    var urlBase = 'pln/php/controllers/empleado.php';

    var url = `${window.location.protocol}//${window.location.hostname}/sayet/pln/php/controllers/empleado.php/finiquito`;

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
        },
        guardarBitacora: function(bita) {
            return comunFact.doPOST(urlBase + '/guardar_bitacora', bita)
        },
        getCatalogo: function(emp){
            return comunFact.doGET(urlBase + '/catalogo');
        },
        getMovimiento: function(obj){
            return comunFact.doGETJ(urlBase + '/get_movimiento', obj)
        }, 
        getFiniquito: function(obj) {
            return $http.post(url, obj, {responseType: 'arraybuffer'}).then(function(response){
                let enivar = {};
                var file = new Blob([response.data], {type: 'application/pdf'});
                var fileURL = URL.createObjectURL(file);
                // para obtener el arhivo para descargar
                enivar.descarga = new File([file], "Finiquito.pdf", { type: 'application/pdf' });
                // para obtener el url del archivo y mostrarlo en pantalla
                enivar.pantalla = $sce.trustAsResourceUrl(fileURL);
                return enivar;
            });
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

