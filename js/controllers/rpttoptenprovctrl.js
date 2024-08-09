(function () {
    const rpttoptenprovctrl = angular.module('cpm.rpttoptenprovctrl', []);

    rpttoptenprovctrl.controller('rptTopTenProvCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', function ($scope, empresaSrvc, jsReportSrvc, authSrvc) {
        $scope.uid = 0;
        $scope.params = { mes: moment().month().toString(), anio: moment().year() };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

        authSrvc.getSession().then(async function (usuario) {
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
            $scope.params.idempresa = usuario.workingon.toString();
        });

        $scope.getTopTenProv = function () {
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : '';
            jsReportSrvc.getPDFReport('BJ8HI76w9', $scope.params).then(function (pdf) { $scope.content = pdf; });
        };

    }]);
}());