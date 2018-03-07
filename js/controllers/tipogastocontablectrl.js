(function(){

    var tipogastocontablectrl = angular.module('cpm.tipogastocontablectrl', ['cpm.tipogastocontablesrvc']);

    tipogastocontablectrl.controller('tipogastocontableCtrl', ['$scope', 'tipogastocontableSrvc', '$confirm', function($scope, tipogastocontableSrvc, $confirm){
        //$scope.tituloPagina = '';

        $scope.elGastocontable = {};
        $scope.lstGastoscontables = [];


        $scope.getLstGastocontable = function(){
            tipogastocontableSrvc.lstGastoscontables().then(function(d){
                $scope.lstGastoscontables = d;
            });
        };

        $scope.editaGastocontable = function(obj, op){
            tipogastocontableSrvc.editRow(obj, op).then(function(){
                $scope.getLstGastocontable();
                $scope.elGastocontable = {};
            });
        };

        $scope.updGastocontable = function(data, idgstcnt){
            data.id = idgstcnt;
            tipogastocontableSrvc.editRow(data, 'u').then(function(){
                $scope.getLstGastocontable();
            });
        };

        $scope.delGastocontable = function(idgstcnt){
            tipogastocontableSrvc.editRow({id:idgstcnt}, 'd').then(function(){
                $scope.getLstGastocontable();
            });
        };

        $scope.getLstGastocontable();

    }]);

}());

