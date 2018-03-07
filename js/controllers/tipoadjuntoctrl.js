(function(){

    var tipoadjuntoctrl = angular.module('cpm.tipoadjuntoctrl', ['cpm.tipoadjuntosrvc']);

    tipoadjuntoctrl.controller('tipoadjuntoCtrl', ['$scope', 'tipoAdjuntoSrvc', '$confirm', function($scope, tipoAdjuntoSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.elTipoadjunto = {};
        $scope.lstTipoadjuntos = [];


        $scope.getLstTipoadjuntos = function(){
            tipoAdjuntoSrvc.lstTipoAdjunto().then(function(d){
                $scope.lstTipoadjuntos = d;
            });
        };

        $scope.editaTipoadjunto = function(obj, op){
            tipoAdjuntoSrvc.editRow(obj, op).then(function(){
                $scope.getLstTipoadjuntos();
                $scope.elTipoadjunto = {};
            });
        };

        $scope.updTipoadjunto = function(data, idtipoadjunto){
             
            data.id = idtipoadjunto;
            tipoAdjuntoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstTipoadjuntos();
            });
        };

        $scope.delTipoadjunto = function(idtipoadjunto){
            $confirm({
                text: "¿Esta seguro(a) de eliminar?",
                title: 'Eliminar',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
            tipoAdjuntoSrvc.editRow({id:idtipoadjunto}, 'd').then(function(){
                $scope.getLstTipoadjuntos();
            });
            });
        };

        $scope.getLstTipoadjuntos();

    }]);

}());

