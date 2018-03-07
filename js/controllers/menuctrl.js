(function(){

    var menuctrl = angular.module('cpm.menuctrl', ['cpm.menusrvc', 'toaster']);

    menuctrl.controller('cpmMenuCtrl', ['$scope', 'menuSrvc', 'toaster', function($scope, menuSrvc, toaster){
        $scope.lstModulos = [];
        $scope.lstMenus = [];
        $scope.lstItems = [];

        $scope.elModulo = {};
        $scope.elMenu = {};
        $scope.elItem = {};

        /*------------------------------------------------------------------------------------------------------*/

        $scope.getLstModulos = function(){
            menuSrvc.getModulos().then(function(mods){
                $scope.lstModulos = mods;
            });
        };

        $scope.addModulo = function(obj){
            menuSrvc.editRow(obj, 'cmod').then(function(){ $scope.getLstModulos(); $scope.elModulo = {}; });
        };

        $scope.updModulo = function(fila, id){
            fila.id = id;
            menuSrvc.editRow(fila, 'umod').then(function(){ $scope.getLstModulos(); });
        };

        $scope.delModulo = function(id){
            menuSrvc.editRow({id:id}, 'dmod').then(function(){ $scope.getLstModulos(); });
        };

        /*------------------------------------------------------------------------------------------------------*/

        $scope.getMenuByModulo = function(){
            menuSrvc.getMenu(parseInt($scope.elMenu.objModulo.id)).then(function(m){
                $scope.lstMenus = m;
            });
        };

        $scope.addMenu = function(obj){
            obj.idmodulo = parseInt($scope.elMenu.objModulo.id);
            menuSrvc.editRow(obj, 'cmnu').then(function(){ $scope.getMenuByModulo(); $scope.elMenu = {}; });
        };

        $scope.updMenu = function(fila, id){
            fila.id = id;
            menuSrvc.editRow(fila, 'umnu').then(function(){ $scope.getLstModulos(); });
        };

        $scope.delMenu = function(id){
            menuSrvc.editRow({id:id}, 'dmnu').then(function(){ $scope.getLstModulos(); });
        };

        /*------------------------------------------------------------------------------------------------------*/

        $scope.getItemsByMenus = function(){
            if(angular.isDefined($scope.elItem.objMenu)){
                menuSrvc.getItems(parseInt($scope.elItem.objMenu.id)).then(function(i){
                    $scope.lstItems = i;
                });
            }else{
                $scope.lstItems = [];
            };
        };

        $scope.addItem = function(obj){
            obj.idmenu = parseInt($scope.elItem.objMenu.id);
            menuSrvc.editRow(obj, 'citem').then(function(){ $scope.getItemsByMenus(); $scope.elItem = {}; });
        };

        $scope.updItem = function(fila, id){
            fila.id = id;
            menuSrvc.editRow(fila, 'uitem').then(function(){ $scope.getItemsByMenus(); });
        };

        $scope.delItem = function(id){
            menuSrvc.editRow({id:id}, 'ditem').then(function(){ $scope.getItemsByMenus(); });
        };

        $scope.getLstModulos();

    }]);

}());
