(function(){

    var unidadctrl = angular.module('cpm.unidadctrl', []);

    unidadctrl.controller('unidadCtrl', ['$scope', 'empresaSrvc', 'unidadSrvc', '$confirm', 'authSrvc', 'DTOptionsBuilder', '$filter', 'tipoLocalSrvc', 'tipoServicioSrvc', function($scope, empresaSrvc, unidadSrvc, $confirm, authSrvc, DTOptionsBuilder, $filter, tipoLocalSrvc, tipoServicioSrvc){

        $scope.unidad = {};
        $scope.unidades = [];
        $scope.empresas = [];
        $scope.unidadStr = '';
        $scope.empDef = {};
        $scope.contador = {};
        $scope.contadores = [];
        $scope.tiposlocales = [];
        $scope.tiposservicios = [];
        $scope.serviciosunidades = [];
        $scope.serviciounidad = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap();

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        tipoLocalSrvc.lstTiposLocal().then(function(d){ $scope.tiposlocales = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.empDef = r[0];
                    $scope.unidad.objEmpresa = $scope.empDef;
                });
            }
        });

        $scope.resetUnidad = function(){
            $scope.unidad = {objEmpresa: $scope.empDef};
            $scope.unidadStr = '';
            $scope.contador = {};
            $scope.contadores = [];
            $scope.serviciosunidades = [];
            $scope.serviciounidad = {};
            goTop();
        };

        function procData(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].idtipolocal = parseInt(d[i].idtipolocal);
                d[i].mcuad = parseFloat(d[i].mcuad);
                d[i].nolineastel = parseInt(d[i].nolineastel);
            };
            return d;
        };

        function procDetData(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idunidad = parseInt(d[i].idunidad);
                d[i].mcubbase = parseFloat(d[i].mcubbase);
            };
            return d;
        };

        $scope.getLstUnidades = function(){
            unidadSrvc.lstUnidades().then(function(d){
                $scope.unidades = procData(d);
            });
        };

        $scope.getLstContadores = function(idunidad){
            unidadSrvc.lstContadores(idunidad).then(function(d){
                $scope.contadores = procDetData(d);
            });
        };

        $scope.getLstServicios = function(idunidad){
            unidadSrvc.lstServicios(idunidad).then(function(d){
                $scope.serviciosunidades = d;
            });
        };

        $scope.getUnidad = function(idunidad){
            unidadSrvc.getUnidad(idunidad).then(function(d){
                $scope.unidad = procData(d)[0];
                $scope.unidad.objEmpresa = $filter('getById')($scope.empresas, $scope.unidad.idempresa);
                $scope.unidad.objTipoLocal = $filter('getById')($scope.tiposlocales, $scope.unidad.idtipolocal);
                $scope.unidadStr = $scope.unidad.nombre + ', ' + $scope.unidad.descripcion;
                tipoServicioSrvc.lstTiposServicios().then(function(d){ $scope.tiposservicios = d; });
                $scope.getLstContadores(idunidad);
                $scope.getLstServicios(idunidad);
                goTop();
            });
        };

        $scope.addUnidad = function(obj){
            obj.idempresa = parseInt(obj.objEmpresa.id);
            obj.nolineastel = obj.nolineastel != null && obj.nolineastel != undefined ? obj.nolineastel : 0;
            obj.numeros = obj.numeros != null && obj.numeros != undefined ? obj.numeros : '';
            obj.conteegsa = obj.conteegsa != null && obj.conteegsa != undefined ? obj.conteegsa : '';
            obj.observaciones = obj.observaciones != null && obj.observaciones != undefined ? obj.observaciones : '';
            obj.idtipolocal = parseInt(obj.objTipoLocal.id);
            unidadSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstUnidades();
                $scope.unidad = {};
                $scope.getUnidad(parseInt(d.lastid));
            });
        };

        $scope.updUnidad = function(obj){
            obj.idempresa = parseInt(obj.objEmpresa.id);
            obj.nolineastel = obj.nolineastel != null && obj.nolineastel != undefined ? obj.nolineastel : 0;
            obj.numeros = obj.numeros != null && obj.numeros != undefined ? obj.numeros : '';
            obj.conteegsa = obj.conteegsa != null && obj.conteegsa != undefined ? obj.conteegsa : '';
            obj.observaciones = obj.observaciones != null && obj.observaciones != undefined ? obj.observaciones : '';
            obj.idtipolocal = parseInt(obj.objTipoLocal.id);
            unidadSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstUnidades();
            });
        };

        $scope.delUnidad = function(idunidad){
            $confirm({text: '¿Seguro(a) de eliminar esta unidad?', title: 'Eliminar unidad', ok: 'Sí', cancel: 'No'}).then(function() {
                unidadSrvc.editRow({id:idunidad}, 'd').then(function(){ $scope.getLstUnidades(); });
            });
        };

        $scope.addContador = function(obj){
            obj.idunidad = $scope.unidad.id;
            obj.alta = obj.alta != null && obj.alta != undefined ? obj.alta : '';
            obj.baja = obj.baja != null && obj.baja != undefined ? obj.baja : '';
            obj.mcubbase = obj.mcubbase != null && obj.mcubbase != undefined ? obj.mcubbase : 0;
            obj.parafactura = obj.parafactura != null && obj.parafactura != undefined ? obj.parafactura : '';
            unidadSrvc.editRow(obj, 'cd').then(function(){
                $scope.getLstContadores($scope.unidad.id);
                $scope.contador = {};
            });
        };

        $scope.delContador = function(idcont){
            $confirm({text: '¿Seguro(a) de eliminar este contador?', title: 'Eliminar contador', ok: 'Sí', cancel: 'No'}).then(function() {
                unidadSrvc.editRow({id:idcont}, 'dd').then(function(){ $scope.getLstContadores($scope.unidad.id); });
            });
        };

        $scope.addServicio = function(obj){
            obj.idunidad = $scope.unidad.id;
            obj.idtiposervicio = obj.objTipoServicio.id;
            unidadSrvc.editRow(obj, 'cs').then(function(){
                $scope.getLstServicios($scope.unidad.id);
                $scope.serviciounidad = {};
            });
        };

        $scope.delServicio = function(idservicio){
            $confirm({text: '¿Seguro(a) de eliminar este servicio?', title: 'Eliminar servicio', ok: 'Sí', cancel: 'No'}).then(function() {
                unidadSrvc.editRow({id:idservicio}, 'ds').then(function(){ $scope.getLstServicios($scope.unidad.id); });
            });
        };

        $scope.getLstUnidades();

    }]);

}());
