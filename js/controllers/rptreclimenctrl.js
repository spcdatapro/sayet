(function () {

    var rptrecclimenctrl = angular.module('cpm.rptrecclimenctrl', []);

    rptrecclimenctrl.controller('rptRecibosClienteMenCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc',
        function ($scope, jsReportSrvc, empresaSrvc, authSrvc) {

            $scope.empresas = [];
            $scope.cargando = false;

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

            $scope.params = {
                fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(),
                idempresa: [], tipo: '1'
            };

            $scope.content = `${window.location.origin}/blank.html`

            var test = false;

            $scope.getRptRecibosCliente = function () {
                $scope.cargando = true;
                $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
                $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
                var rpttest = 'Sk_TXxlMQ', rpt = 'ryKtNbFFq';


                jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function (pdf) { 
                    $scope.content = pdf; 
                    $scope.cargando = false;
                });
            };

            $scope.params_auditoria = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate() }

            $scope.getRptRecibosAuditoria = params => {
                $scope.cargando = true;
                params.fdelstr = moment(params.fdel).isValid() ? moment(params.fdel).format('YYYY-MM-DD') : '';
                params.falstr = moment(params.fal).isValid() ? moment(params.fal).format('YYYY-MM-DD') : '';

                jsReportSrvc.getPDFReport('B1y5mEE3xe', params).then(pdf => {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            $scope.getRptRecibosAuditoriaXml = params => {
                $scope.cargando = true;
                params.fdelstr = moment(params.fdel).isValid() ? moment(params.fdel).format('YYYY-MM-DD') : '';
                params.falstr = moment(params.fal).isValid() ? moment(params.fal).format('YYYY-MM-DD') : '';
                try {
                    jsReportSrvc.getReport('B17FZ9Snge', params).then(function (result) {
                        var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                        let rango = moment(params.del).format('DD-MM-YYYY') + '_' + moment(params.anio).format('DD-MM-YYYY');

                        saveAs(file, 'Reporte_recibos_' + rango + '.xlsx');
                        $scope.cargando = false;
                    })
                } catch (err) {
                    console.log(err);
                    $scope.cargando = false;
                }
            }

        }]);
}());