(function(){

    const rptarchivondctrl = angular.module('cpm.rptarchivondctrl', []);

    rptarchivondctrl.controller('rptArchivoNDCtrl', ['$scope', '$window', 'empresaSrvc', 'bancoSrvc', ($scope, $window, empresaSrvc, bancoSrvc) => {

        $scope.params = { fecha: moment().toDate(), idempresa: undefined, idbanco: undefined};
        $scope.empresas = [];
        $scope.bancos = [];

        empresaSrvc.lstEmpresas().then((d) => $scope.empresas = d);

        $scope.loadBancos = (idempresa) => bancoSrvc.lstBancosActivos(idempresa).then((d) => $scope.bancos = d);

        $scope.getArchivo = () => {
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            const qstr = `${$scope.params.fechastr}/${$scope.params.idbanco}`;
            $window.open(`php/generaarchivond.php/gettxt/${qstr}`);
        };

    }]);

}());
