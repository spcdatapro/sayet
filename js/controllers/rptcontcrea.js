(function () {

    var rptrecclimenctrl = angular.module('cpm.rptcontcrea', []);

    rptrecclimenctrl.controller('rptContratosCreados', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc',
        function ($scope, jsReportSrvc, empresaSrvc, authSrvc) {

            $scope.empresas = [];

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

            $scope.params = {
                anio: moment().year(), idempresa: undefined
            };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getRptContratos = function () {
                $scope.params.anio;
                $scope.params.idempresa;
                var rpttest = 'rkKYL3WBj', rpt = 'rkKYL3WBj';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());