(function(){

    var tipomovtranbanctrl = angular.module('cpm.tipomovtranbanctrl', ['cpm.tipomovtranbansrvc']);

    tipomovtranbanctrl.controller('tipomovtranbanCtrl', ['$scope', 'tipoMovTranBanSrvc', '$confirm', function($scope, tipoMovTranBanSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.elTipomovtranban = {};
        $scope.lstTipomovtranbans = [];


        $scope.getLstTipoMovTranBan = function(){
            tipoMovTranBanSrvc.lstTiposMovTB().then(function(d){
                $scope.lstTipomovtranbans = d;
            });
        };

        $scope.editaTipoMovTranBan = function(obj, op){
            tipoMovTranBanSrvc.editRow(obj, op).then(function(){
                $scope.getLstTipoMovTranBan();
                $scope.elTipomovtranban = {};
            });
        };

        $scope.updTipoMovTranBan = function(data, idtipomov){
             
            data.id = idtipomov;
            tipoMovTranBanSrvc.editRow(data, 'u').then(function(){
                $scope.getLstTipoMovTranBan();
            });
        };

        $scope.delTipoMovTranBan = function(idtipomov){
            $confirm({
                text: "¿Esta seguro(a) de eliminar?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            tipoMovTranBanSrvc.editRow({id:idtipomov}, 'd').then(function(){
                $scope.getLstTipoMovTranBan();
            });
            });
        };

        $scope.getLstTipoMovTranBan();

    }]);

}());

