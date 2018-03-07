(function(){

    var tipoactivoctrl = angular.module('cpm.tipoactivoctrl', ['cpm.tipoactivosrvc']);

    tipoactivoctrl.controller('tipoactivoCtrl', ['$scope', 'tipoactivoSrvc', '$confirm', function($scope, tipoactivoSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.elTipoactivo = {};
        $scope.losTipoActivos = [];


        $scope.getLstTipoActivos = function(){
            tipoactivoSrvc.lstTipoActivo().then(function(d){
                $scope.losTipoActivos = d;
            });
        };

        $scope.editaTipoActivo = function(obj, op){
            tipoactivoSrvc.editRow(obj, op).then(function(){
                $scope.getLstTipoActivos();
                $scope.elTipoActivo = {};
            });
        };

        $scope.updTipoActivo = function(data, idtipoactivo){
             
            data.id = idtipoactivo;
            tipoactivoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstTipoActivos();
            });
        };

        $scope.delTipoActivo = function(idtipoactivo){
            $confirm({
                text: "¿Esta seguro(a) de eliminar?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            tipoactivoSrvc.editRow({id:idtipoactivo}, 'd').then(function(){
                $scope.getLstTipoActivos();
            });
            });
        };

        $scope.getLstTipoActivos();

    }]);

}());

