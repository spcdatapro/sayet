(function(){

    var tipoproyectoctrl = angular.module('cpm.tipoproyectoctrl', ['cpm.tipoproyectosrvc']);

    tipoproyectoctrl.controller('tipoproyectoCtrl', ['$scope', 'tipoProyectoSrvc', '$confirm', function($scope, tipoProyectoSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.elTipoproyecto = {};
        $scope.lstTipoproyectos = [];


        $scope.getLstTipoproyectos = function(){
            tipoProyectoSrvc.lstTipoProyecto().then(function(d){
                $scope.lstTipoproyecto = d;
            });
        };

        $scope.editaTipoproyecto = function(obj, op){
            tipoProyectoSrvc.editRow(obj, op).then(function(){
                $scope.getLstTipoproyectos();
                $scope.elTipoproyecto = {};
            });
        };

        $scope.updTipoproyecto = function(data, idtipoproyecto){
             
            data.id = idtipoproyecto;
            tipoProyectoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstTipoproyectos();
            });
        };

        $scope.delTipoproyecto = function(idtipoproyecto){
            $confirm({
                text: "¿Esta seguro(a) de eliminar?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            tipoProyectoSrvc.editRow({id:idtipoproyecto}, 'd').then(function(){
                $scope.getLstTipoproyectos();
            });
            });
        };

        $scope.getLstTipoproyectos();

    }]);

}());

