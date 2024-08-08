(function () {

    var rptrecclimenctrl = angular.module('cpm.rptrecclimenctrl', []);

    rptrecclimenctrl.controller('rptRecibosClienteMenCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc',
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
                fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(),                
                idempresa: [], tipo: '1'
            };

            $scope.content = `${window.location.origin}/sayet/blank.html`;

            var test = false;

            $scope.getRptRecibosCliente = function () {
                $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
                $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
                var rpttest = 'Sk_TXxlMQ', rpt = 'ryKtNbFFq';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { $scope.content = pdf; });
            };

        }]);
}());