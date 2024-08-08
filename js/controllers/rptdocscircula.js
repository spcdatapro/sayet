(function () {

    var rptdocscirculactrl = angular.module('cpm.rptdocscirculactrl', []);

    rptdocscirculactrl.controller('rptDocsCirculaCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'jsReportSrvc', '$filter', function ($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, jsReportSrvc, $filter) {

        $scope.bancos = [];
        $scope.empresas = [];
        $scope.params = { fal: moment().toDate() };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.cargando = false;

        authSrvc.getSession().then(function (usrLogged) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
            $scope.params.idempresa = usrLogged.workingon.toString();
            $scope.getBancos(usrLogged.workingon);
        });

        // traer banco al cambiar empresa
        $scope.getBancos = function (idempresa) {
            bancoSrvc.lstBancos(idempresa).then(function (d) {
                $scope.params.idbanco = undefined;
                $scope.bancos = d;
            });
        }

        $scope.getDocsCirculando = function () {
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('HyxTB2Oub', $scope.params).then(function (pdf) {
                $scope.content = pdf;
                $scope.cargando = false;
            });
        }

        // excel
        $scope.getXML = function (params) {
            // estatus de carga
            $scope.cargando = true;

            params.falstr = moment(params.fal).format('YYYY-MM-DD');

            jsReportSrvc.getReport('ryLtAHrKC', params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                let rango = undefined;
                let banco = undefined;

                banco = $filter('getById')($scope.bancos, params.idbanco).siglas;
                rango = params.falstr;

                saveAs(file, 'Documentos_circulacion_' + banco + '_' + rango + '.xlsx');

                $scope.cargando = false;
            });
        }

    }]);

}());
