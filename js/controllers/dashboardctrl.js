(function(){

    var dashboardctrl = angular.module('cpm.dashboardctrl', []);

    dashboardctrl.controller('dashBoardCtrl', ['$scope', 'dashBoardSrvc', 'DTOptionsBuilder', 'authSrvc', '$uibModal', 'toaster', '$confirm', function($scope, dashBoardSrvc, DTOptionsBuilder, authSrvc, $uibModal, toaster, $confirm){
        $scope.favoritos = [];
        $scope.favs = [];
        $scope.usrdata = {};

        enablePopOvers();

        authSrvc.getSession().then(function(usrLogged){
            $scope.usrdata = usrLogged;
            $scope.getFavoritos($scope.usrdata.uid);
        });

        $scope.getFavoritos = function(idusuario){
            dashBoardSrvc.favUsr(idusuario).then(function(d){
                $scope.favs = d;
                $scope.setfavs();
            });
        };

        $scope.setfavs = function(){
            $scope.favoritos = [];
            for(var i = 0; i < 7; i++){ $scope.favoritos.push({posicion: 0, modulo: '', menu: '', descitemmenu: '', url: ''}); }

            for(i = 0; i < $scope.favs.length; i++){
                $scope.favoritos[$scope.favs[i].posicion].posicion = $scope.favs[i].posicion;
                $scope.favoritos[$scope.favs[i].posicion].modulo = $scope.favs[i].modulo;
                $scope.favoritos[$scope.favs[i].posicion].menu = $scope.favs[i].menu;
                $scope.favoritos[$scope.favs[i].posicion].descitemmenu = $scope.favs[i].descitemmenu;
                $scope.favoritos[$scope.favs[i].posicion].url = $scope.favs[i].url;
            }
        };

        $scope.asigfav = function(pos){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAsignaFavorito.html',
                controller: 'ModalAsignaFavoritoCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    pos: function(){ return pos; },
                    usr: function() { return $scope.usrdata; }
                }
            });

            modalInstance.result.then(function(){
                $scope.getFavoritos($scope.usrdata.uid);
            }, function(){ return 0; });

        };

        $scope.freepos = function(pos){
            $confirm({text: '¿Seguro(a) de liberar este espacio?', title: 'Liberar espacio en favoritos', ok: 'Sí', cancel: 'No'}).then(function() {
                dashBoardSrvc.editRow({idusr:$scope.usrdata.uid, posicion: pos}, 'ffav').then(function(){ $scope.getFavoritos($scope.usrdata.uid); });
            });
        };
    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    dashboardctrl.controller('ModalAsignaFavoritoCtrl', ['$scope', '$uibModalInstance', 'toaster', 'dashBoardSrvc', 'pos', 'usr', function($scope, $uibModalInstance, toaster, dashBoardSrvc, pos, usr){

        $scope.favspend = [];
        $scope.favorito = { iditemmenu: '0', posicion: pos, idusr: usr.uid };

        dashBoardSrvc.favPendientes(usr.uid).then(function(d){ $scope.favspend = d });

        $scope.ok = function () {
            //console.log($scope.favorito); $uibModalInstance.close();
            dashBoardSrvc.editRow($scope.favorito, 'cfav').then(function(){ $uibModalInstance.close(); });
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };


    }]);

}());
