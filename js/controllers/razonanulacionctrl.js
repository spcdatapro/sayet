(function(){

    var razonanulacionctrl = angular.module('cpm.razonanulacionctrl', []);

    razonanulacionctrl.controller('razonAnulacionCtrl', ['$scope', 'razonAnulacionSrvc', 'authSrvc', '$route', '$confirm', function($scope, razonAnulacionSrvc, authSrvc, $route, $confirm){
        //$scope.tituloPagina = 'CPM';

        $scope.razon = {};
        $scope.razones = [];
        $scope.permiso = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
            }
        });

        $scope.resetRazon = function(){ $scope.razon = {}; };

        $scope.getLstRazones = function(){ razonAnulacionSrvc.lstRazones().then(function(d){ $scope.razones = d; }); };

        $scope.getRazon = function(idrazon){
            razonAnulacionSrvc.getRazon(idrazon).then(function(d){
                $scope.razon = d[0];
                goTop();
            });
        };

        $scope.addRazon = function(obj){
            razonAnulacionSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstRazones();
                $scope.getRazon(parseInt(d.lastid));
            });
        };

        $scope.updRazon = function(obj, id){
            obj.id = id;
            razonAnulacionSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstRazones();
                $scope.getRazon(parseInt(id));
            });
        };

        $scope.delRazon = function(id){
            $confirm({text: '¿Seguro(a) de eliminar esta razón de anulación?', title: 'Eliminar razón de anulación', ok: 'Sí', cancel: 'No'}).then(function() {
                razonAnulacionSrvc.editRow({id: id}, 'd').then(function(){ $scope.getLstRazones(); });
            });
        };

        $scope.getLstRazones();
    }]);

}());
