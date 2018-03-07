(function(){

    var monedactrl = angular.module('cpm.monedactrl', ['cpm.monedasrvc']);

    monedactrl.controller('monedaCtrl', ['$scope', 'monedaSrvc', function($scope, monedaSrvc){
        //$scope.tituloPagina = 'CPM';

        $scope.laMoneda = {};
        $scope.lasMonedas = [];

        $scope.getLstMonedas = function(){
            monedaSrvc.lstMonedas().then(function(d){
                $scope.lasMonedas = d;
            });
        };

        $scope.addMoneda = function(obj){
            monedaSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstMonedas();
                $scope.laMoneda = {};
            });
        };

        $scope.updMoneda = function(data, id){
            data.id = id;
            monedaSrvc.editRow(data, 'u').then(function(){
                $scope.getLstMonedas();
            });
        };

        $scope.delMoneda = function(id){
            monedaSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstMonedas();
            });
        };

        $scope.getLstMonedas();
    }]);

}());