(function(){

    var cierreanualctrl = angular.module('cpm.cierreanualctrl', []);

    cierreanualctrl.controller('cierreAnualCtrl', ['$scope', 'cierreAnualSrvc', 'authSrvc', '$confirm', '$route', function($scope, cierreAnualSrvc, authSrvc, $confirm, $route){

        $scope.params = {idempresa: 0, anio: moment().year()};
        $scope.permiso = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        $scope.resetData = function(){
            $scope.params = {idempresa: $scope.params.idempresa, anio: moment().year()};
        };

        $scope.generar = function(){
            cierreAnualSrvc.existe($scope.params.idempresa, $scope.params.anio).then(function(d){
                if(+d.existe === 1){
                    $confirm({text: 'Ya existe el cierre anual para el año ' + $scope.params.anio + '. Debe eliminar las partidas directas para regenerar.',
                        title: 'Partidas de cierre', ok: 'Ok', cancel: 'Cancel'}).then(function() { }
                    );
                }else{
                    cierreAnualSrvc.cierreAnual($scope.params).then(function(d){
                        $confirm({text: 'Se generaron las siguientes partidas directas: ' + d.generadas,
                            title: 'Partidas de cierre', ok: 'Ok', cancel: 'Cancel'}).then(function() { }
                        );
                    });
                }
            });
        };
    }]);

}());
