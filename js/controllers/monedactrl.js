(function(){

    const monedactrl = angular.module('cpm.monedactrl', ['cpm.monedasrvc']);

    monedactrl.controller('monedaCtrl', ['$scope', 'monedaSrvc', ($scope, monedaSrvc) => {

        $scope.laMoneda = {nommoneda: undefined, simbolo: undefined};
        $scope.lasMonedas = [];

        $scope.getLstMonedas = () => monedaSrvc.lstMonedas().then((d) => $scope.lasMonedas = d);

        $scope.getMoneda = (idmoneda) => monedaSrvc.getMoneda(+idmoneda).then((d) => $scope.laMoneda = d[0]);

        $scope.resetMoneda = () => $scope.laMoneda = {nommoneda: undefined, simbolo: undefined};

        $scope.addMoneda = (obj) => {
            monedaSrvc.editRow(obj, 'c').then(() => {
                $scope.getLstMonedas();
                $scope.resetMoneda();
            });
        };

        $scope.updMoneda = (data) => monedaSrvc.editRow(data, 'u').then(() => $scope.getLstMonedas());

        $scope.getLstMonedas();
    }]);

}());