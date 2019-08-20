(function(){

    const confaprotsctrl = angular.module('cpm.confaprotsctrl', []);

    confaprotsctrl.controller('ConfigAprOtsCtrl', ['$scope', 'presupuestoSrvc', ($scope, presupuestoSrvc) => {

        $scope.usuarios = [];

        prepData = (d) => {
            d.map((u) => u.limiteot = parseFloat(u.limiteot));
            return d;
        };

        $scope.getLstUsuarios = () => presupuestoSrvc.usrApruebanOts().then((d) => $scope.usuarios = prepData(d));

        $scope.updUsuario = (data) => presupuestoSrvc.editRow(data, 'usrmonto').then(() => $scope.getLstUsuarios());

        $scope.getLstUsuarios();
    }]);

}());