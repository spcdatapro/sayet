(function(){

    var solicitudotctrl = angular.module('cpm.solicitudotctrl', ['cpm.solicitudotsrvc']);

    solicitudotctrl.controller('solicitudotCtrl', ['$scope', 'solicitudotSrvc', 'empresaSrvc', 'tipogastoSrvc', 'tipogastocontableSrvc', 'proveedorSrvc', 'proyectoSrvc', '$confirm', function($scope, solicitudotSrvc, empresaSrvc, tipogastoSrvc, tipogastocontableSrvc, proveedorSrvc, proyectoSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.laSolicitud = {};
        $scope.lasSolicitudes = [];
        $scope.lasEmpresas = [];
        $scope.losTipoGastos = [];
        $scope.losGastosContables = [];
        $scope.losProvs = [];
        $scope.losProyectos = [];


        function dateToStr(fecha){ return fecha !== null && fecha !== undefined ? (fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate()) : ''; };


        $scope.resetlaSolicitud = function(){
            $scope.laSolicitud = {
                id: 0,
                idtipogasto : 0,
                idgastocontable : 0,
                idempresa : 0,
                idproveedor : 0,
                idproyecto: 0,
                fechasolicitud: moment().toDate(),
                numorden : '',
                valormaterial : '',
                valorobramano : '',
                montosolicita : '',
                descripcion : '',
                trabajomayor : 0,
                ivaincluido : 0,
                dolares : 0,
            };
        };

        $scope.resetlaSolicitud();
            
            //Esta funcion (empresaSrvc.lstEmpresas) no acepta parámetros.
            // Podes revisar su definición en el archivo empresasrvc.js
        $scope.getLstEmpresas = function(){
            empresaSrvc.lstEmpresas().then(function(d){
                $scope.lasEmpresas = d;
            });
        };
            //Esta funcion (tipogastoSrvc.lstTipogastos) no acepta parámetros.
            // Podes revisar su definición en el archivo tipogastosrvc.js
        $scope.getLstTipogastos = function(){
            tipogastoSrvc.lstTipogastos().then(function(d){
                $scope.losTipoGastos = d;
            });
        };
            //Esta funcion (gastocontableSrvc.lstGastoscontables) no acepta parámetros.
            // Podes revisar su definición en el archivo gastocontablesrvc.js
        $scope.getLstGastoscontables = function(){
            tipogastocontableSrvc.lstGastoscontables().then(function(d){
                $scope.losGastosContables = d;
            });
        };
            //Esta funcion (proveedorSrvc.lstProveedores) no acepta parámetros.
            // Podes revisar su definición en el archivo proveedorsrvc.js
        $scope.getLstProveedores = function(){
            proveedorSrvc.lstProveedores().then(function(d){
                $scope.losProvs = d;   
            });
        };

        $scope.getLstProyectos = function(){
            proyectoSrvc.lstProyecto().then(function(d){
                $scope.losProyectos = d;
            });
        };


        $scope.getLstSolicitudes = function(){
            solicitudotSrvc.lstSolicitudes().then(function(d){
                $scope.lasSolicitudes = d;
            });
        };

        $scope.getSolicitud = function(idsolicitud){
            solicitudotSrvc.getSolicitud(idsolicitud).then(function(d){
                $scope.laSolicitud = d;
                tipogastoSrvc.getTipogasto(d.idtipogasto).then(function(resTiposolicitud){
                    $scope.laSolicitud.objTipogasto = resTipogasto[0];
            
                });

                tipogastocontableSrvc.getGastocontable(d.idgastocontable).then(function(resGastocontable){
                    $scope.laSolicitud.objGastoContable = resGastocontable[0];
                
                });
                
                empresaSrvc.getEmpresa(d.idempresa).then(function(resEmpresa){
                    $scope.laSolicitud.objEmpresa = resEmpresa[0];
            
                });

                proveedorSrvc.getProveedor(d.idproveedor).then(function(resProveedor){
                    $scope.laSolicitud.objProveedor = resProveedor[0];
                
                });
                proyectoSrvc.getProyecto(d.idproyecto).then(function(resProyecto){
                    $scope.laSolicitud.objProyecto = resProyecto[0];
                });


                $scope.getLstTipogastos();
                $scope.getLstGastoscontables();
                $scope.getLstEmpresas();
                $scope.getLstProveedores();
                $scope.getLstProyectos()
            });
        };

        

        $scope.addSolicitud = function(obj){
            obj.idtipogasto = $scope.laSolicitud.objTipogasto.id;
            obj.idgastocontable = $scope.laSolicitud.objGastoContable.id;
            obj.idempresa = $scope.laSolicitud.objEmpresa.id;
            obj.idproveedor = $scope.laSolicitud.objProveedor.id;
            obj.idproyecto = $scope.laSolicitud.objProyecto.id;
            obj.fechasolicitud = dateToStr(obj.fechasolicitud);
                /*
                *
                * Según podes ver en el archivo solicitudotsrvc.js, la función 'editRow' acepta dos parámetros:
                * El primero es la linea que debe procesar, ya sea para insertarla actualizarla o eliminarla.
                * El segundo es la acción que debe ejecutar segun la definición de acciones del
                * archivo php (solicitudot.php). La función 'editRow' envia una solicitud del tipo POST al
                * servidor y segun el archivo php las acciones del tipo POST para este proceso son
                * c: insert, u: update y d: delete.
                *
                * */

                solicitudotSrvc.editRow(obj, 'c').then(function(){
                $scope.resetlaSolicitud();
            });
        };

        /*$scope.updSolicitud = function(data, idslctd){
             $confirm({
                text: "¿Esta seguro(a) de Actualizar?",
                title: 'Actualizar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            data.id = idslctd;
            solicitudotSrvc.editRow(data, 'u').then(function(){
                $scope.getLstSolicitudes();
            });
            });
        };

        $scope.delSolicitud = function(idslctd){
            $confirm({
                text: "¿Esta seguro(a) de eliminar?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            solicitudotSrvc.editRow({id:idslctd}, 'd').then(function(){
                $scope.getLstSolicitudes();
            });
            });
        };*/

        $scope.getScope = function () {
            console.log($scope);
        };

        $scope.getLstTipogastos();
        $scope.getLstGastoscontables();
        $scope.getLstEmpresas();
        $scope.getLstProveedores();
        $scope.getLstProyectos();

    }]);

}());

