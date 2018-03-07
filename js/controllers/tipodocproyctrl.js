(function(){

    var tipodocproyctrl = angular.module('cpm.tipodocproyctrl', []);

    tipodocproyctrl.controller('tipoDocProyCtrl', ['$scope', 'tipoDocProySrvc', '$confirm', function($scope, tipoDocProySrvc, $confirm){
        $scope.tipodoc = {};
        $scope.lsttiposdoc = [];

        $scope.getLstTiposDoc = function(){
            tipoDocProySrvc.lstTiposDocProy().then(function(d){
                $scope.lsttiposdoc = d;
            });
        };

        $scope.addTipoDoc = function(obj){
            tipoDocProySrvc.editRow(obj, 'c').then(function(){
                $scope.getLstTiposDoc();
                $scope.tipodoc = {};
            });
        };

        $scope.updTipoDoc = function(data, id){
            data.id = id;
            tipoDocProySrvc.editRow(data, 'u').then(function(){
                $scope.getLstTiposDoc();
            });
        };

        $scope.delTipoDoc = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este tipo de documento?', title: 'Eliminar tipo de documento', ok: 'Sí', cancel: 'No'}).then(function() {
                tipoDocProySrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstTiposDoc(); });
            });
        };

        $scope.getLstTiposDoc();
    }]);

}());
