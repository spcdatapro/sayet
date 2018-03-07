(function(){

    var tiposervicioctrl = angular.module('cpm.tiposervicioctrl', []);

    tiposervicioctrl.controller('tipoServicioCtrl', ['$scope', 'tipoServicioSrvc', '$confirm', function($scope, tipoServicioSrvc, $confirm){

        $scope.tipo = {};
        $scope.tipos = [];

        $scope.getLstTipos = function(){ tipoServicioSrvc.lstTiposServicios().then(function(d){ $scope.tipos = d; }); };

        $scope.addTipo = function(obj){
            tipoServicioSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstTipos();
                $scope.tipo = {};
            });
        };

        $scope.updTipo = function(obj, idtipo){
            obj.id = idtipo;
            tipoServicioSrvc.editRow(obj, 'u').then(function(){ $scope.getLstTipos(); });
        };

        $scope.delTipo = function(idtipo){
            $confirm({
                text: "¿Esta seguro(a) de eliminar este tipo de servicio?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
                    tipoServicioSrvc.editRow({id: idtipo}, 'd').then(function(){ $scope.getLstTipos(); });
                });
        };

        $scope.getLstTipos();

    }]);

}());
