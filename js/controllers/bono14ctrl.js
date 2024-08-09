(function () {

    var controller = angular.module('cpm.rptbono14', []);

    controller.controller('rptBono14', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'proyectoSrvc', 'authSrvc', function ($scope, 
        jsReportSrvc, empresaSrvc, proyectoSrvc, authSrvc) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];

            // estatus de carga
            $scope.cargando = false;

            // parametros para reporte
            $scope.params = { anio: +moment().toDate().getFullYear().toString(), agrupar: '1' };

            // para visualizaciones en pantalla
            $scope.content = `${window.location.origin}/sayet/blank.html`;

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

            // traer proyectos al cambiar empresa
            $scope.getProyectos = function (idempresa) {
                proyectoSrvc.lstProyectosPorEmpresa(idempresa).then(function (d) { $scope.proyectos = d; });
                $scope.params.idproyecto = undefined;
            };

            // pdf
            $scope.getPDF = function (params) {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getPDFReport('HyxyemVOA', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            // excel
            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('S1D-eQEdC', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;

                    saveAs(file, 'Reporte_Bono_14_' + rango + '.xlsx');

                    $scope.cargando = false;
                });
            };
        }]);
}());