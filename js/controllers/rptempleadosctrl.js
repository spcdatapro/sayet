(function () {

    var controller = angular.module('cpm.rptempleados', []);

    controller.controller('rptEmpleados', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc',
        function ($scope, jsReportSrvc, empresaSrvc, authSrvc) {

            $scope.empresas = [];
            $scope.params = { inactivos: 0, agrupar: '1', fecha: moment().toDate() };
            $scope.content = `${window.location.origin}/sayet/blank.html`;
            $scope.cargando = false;

            // asignar la empresa en la que el usuario se encuentra
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

            empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });

            $scope.getLibSalarios = function () {
                $scope.cargando = true;
                $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');

                jsReportSrvc.getPDFReport('S1C2VP90j', $scope.params).then(function (pdf) { 
                    $scope.content = pdf; 
                    $scope.cargando = false;
                });
            }

            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                // ajustar fechas
                params.fechastr = moment(params.fal).format('YYYY-MM-DD');

                jsReportSrvc.getReport('S1tcSwGq0', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;

                    rango = params.fechastr;
                    saveAs(file, 'Lista_empleados_' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            }
        }]);
}());