(function(){

    var tiposervicioventactrl = angular.module('cpm.tiposervicioventactrl', []);

    tiposervicioventactrl.controller('tipoServicioVentaCtrl', ['$scope', 'tipoServicioVentaSrvc', '$confirm', function($scope, tipoServicioVentaSrvc, $confirm){

        $scope.tipo = {};
        $scope.tipos = [];

        $scope.getLstTipos = function(){ tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tipos = d; }); };

        $scope.addTipo = function(obj){
            tipoServicioVentaSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstTipos();
                $scope.tipo = {};
            });
        };

        $scope.updTipo = function(obj, idtipo){
            obj.id = idtipo;
            tipoServicioVentaSrvc.editRow(obj, 'u').then(function(){ $scope.getLstTipos(); });
        };

        $scope.delTipo = function(idtipo){
            $confirm({
                text: "¿Esta seguro(a) de eliminar este tipo de servicio de venta?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
                    tipoServicioVentaSrvc.editRow({id: idtipo}, 'd').then(function(){ $scope.getLstTipos(); });
                });
        };

        $scope.getLstTipos();

    }]);

}());