(function () {

    var rptcorrchctrl = angular.module('cpm.rptcorrelageren', []);

    rptcorrchctrl.controller('rptCorrelaGeren', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', 'tipoMovTranBanSrvc', 'toaster', function ($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc, tipoMovTranBanSrvc, toaster) {

        $scope.losBancos = [];
        $scope.params = { fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), tipo: '1' };
        $scope.objBanco = undefined;
        $scope.content = `${window.location.origin}/blank.html`
        $scope.tipos = [];
        $scope.empresas = [];
        $scope.cargando = false;

        tipoMovTranBanSrvc.lstTiposMovGT().then(function (d) { $scope.tipos = d; });

        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function (d) {
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                });
            });
        });

        $scope.getCorrelativosCheques = function (params) {
            $scope.cargando = true;
            $scope.content = `${window.location.origin}/blank.html`
            $scope.params.porproyecto = params.tipo == 1 ? false : true;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('HJVdFHq_i', $scope.params).then(function (pdf) {
                $scope.content = pdf;
                $scope.cargando = false;
            });
        };

        $scope.getCorrelativosChequesExcel = function (params) {
            $scope.cargando = true;
            $scope.content = `${window.location.origin}/blank.html`
            $scope.params.porproyecto = params.tipo == 1 ? false : true;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');

            jsReportSrvc.getReport('rJ6ttH9us', $scope.params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                var nombre = 'Concepto de debitos del ' + moment($scope.params.fDel).format('DD/MM/YYYY') + ' al ' + moment($scope.params.fAl).format('DD/MM/YYYY');
                saveAs(file, nombre + '.xlsx');
                $scope.cargando = false;
            });
        };

    }]);

}());
