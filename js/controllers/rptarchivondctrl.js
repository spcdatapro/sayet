(function(){

    const rptarchivondctrl = angular.module('cpm.rptarchivondctrl', []);

    rptarchivondctrl.controller('rptArchivoNDCtrl', ['$scope', '$window', 'empresaSrvc', 'bancoSrvc', 'authSrvc', ($scope, $window, empresaSrvc, bancoSrvc, authSrvc) => {

        $scope.params = { fecha: moment().toDate(), idempresa: undefined, idbanco: undefined};
        $scope.empresas = [];
        $scope.bancos = [];

        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
        });

        $scope.loadBancos = (idempresa) => bancoSrvc.lstBancosActivos(idempresa).then((d) => $scope.bancos = d);

        $scope.getArchivo = () => {
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            const qstr = `${$scope.params.fechastr}/${$scope.params.idbanco}`;
            $window.open(`php/generaarchivond.php/gettxt/${qstr}`);
        };

    }]);

}());
