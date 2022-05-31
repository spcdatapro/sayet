(function () {
    const rpttoptenprovctrl = angular.module('cpm.rpttoptenprovctrl', []);

    rpttoptenprovctrl.controller('rptTopTenProvCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', function ($scope, empresaSrvc, jsReportSrvc, authSrvc) {
        $scope.empresa = {};
        $scope.uid = 0;
        $scope.params = { mes: moment().month().toString(), anio: moment().year(), idempresa: $scope.empresa };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

        authSrvc.getSession().then(async function (usrLogged) {
            $scope.uid = +usrLogged.uid;
            if (parseInt(usrLogged.workingon) > 0) {
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function (d) {
                    $scope.empresa = d[0];
                });
            }
        });

        $scope.getTopTenProv = function () {
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : '';
            jsReportSrvc.getPDFReport('BJ8HI76w9', $scope.params).then(function (pdf) { $scope.content = pdf; });
        };

    }]);
}());