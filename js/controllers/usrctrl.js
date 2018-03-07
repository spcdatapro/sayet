(function(){

    var usrctrl = angular.module('cpm.usrctrl', ['cpm.authsrvc', 'toaster']);

    usrctrl.controller('cpmUsrCtrl', ['$scope', 'authSrvc', 'toaster', 'DTOptionsBuilder', function($scope, authSrvc, toaster, DTOptionsBuilder){
        $scope.tituloPagina = 'Perfil de ';

        $scope.perfil = {};
        $scope.permisos = [];
        $scope.editando = true;
        $scope.losPerfiles = [];

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap();

        function procDataPerfil(data){
            data.id = parseInt(data.id);
            return data;
        };

        $scope.getLosPermisos = function(){
            authSrvc.getPermisos($scope.perfil.id).then(function(respuesta){
                for(var i = 0; i < respuesta.length; i++){
                    respuesta[i].accesar = parseInt(respuesta[i].accesar);
                    respuesta[i].crear = parseInt(respuesta[i].crear);
                    respuesta[i].modificar = parseInt(respuesta[i].modificar);
                    respuesta[i].eliminar = parseInt(respuesta[i].eliminar);
                }
                $scope.permisos = respuesta;
                $scope.editando = true;

                $("#divPerfiles").removeClass("active");
                $('#divFrmDatosGenerales').addClass('active');
                $('.nav-tabs a[href="#divFrmDatosGenerales"]').tab('show')
            });
        };

        $scope.halaPerfil = function(idusr){
            authSrvc.getPerfil(idusr).then(function(p){
                $scope.perfil =  procDataPerfil(p[0]);
                $scope.getLosPermisos();
            });
        };

        authSrvc.getSession().then(function(usrLogged){
            $scope.halaPerfil(parseInt(usrLogged.uid));
        });

        $scope.getLstPerfiles = function(){
            authSrvc.lstPerfiles().then(function(d){ $scope.losPerfiles = d; });
        };

        $scope.getLstPerfiles();

        $scope.resetPerfil = function(){
            $scope.perfil = {};
            $scope.permisos = [];
            $scope.editando = false;
        };

        $scope.addPerfil = function(obj){
            authSrvc.addPerfil(obj).then(function(d){
                $scope.halaPerfil(parseInt(d.lastid));
                $scope.getLstPerfiles();
            });
        };

        $scope.savePerfil = function(obj){
            authSrvc.updPerfil(obj).then(function(){
                toaster.pop('success', 'Perfil', 'Actualizado');
            });
        };

        $scope.updPermiso = function(tipo, idpermiso, valor){
            var obj = {tipo: tipo, idpermiso: idpermiso, valor: valor};
            authSrvc.setPermiso(obj).then(function(){
                toaster.pop('success', 'Permiso', 'Actualizado', 'timeout:1000');
            });
        };

    }]);

}());
