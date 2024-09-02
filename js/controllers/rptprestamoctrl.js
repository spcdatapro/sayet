(function () {

    var controller = angular.module('cpm.vacaciones', []);

    controller.controller('rptVacaciones', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc', 'empServicios', function ($scope, jsReportSrvc, empresaSrvc,
        authSrvc, empServicios) {

        // variables para selectores
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.empleados = [];

        // estatus de carga
        $scope.cargando = false;

        // parametros para reporte
        $scope.params = { agrupar: '1', anio: +moment().toDate().getFullYear().toString() };

        empServicios.buscar({ estatus: 1, sin_limite: true }).then(function (d) {
            $scope.empleados = d.resultados;
        });

        // para visualizaciones en pantalla
        $scope.content = `${window.location.origin}/sayet/blank.html`;

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

        // pdf
        $scope.getPDF = function (params) {
            // estatus de carga
            $scope.cargando = true;

            jsReportSrvc.getPDFReport('B18Mvr690', params).then(function (pdf) {
                $scope.content = pdf;
                $scope.cargando = false;
            });
        };

        // excel
        $scope.getXML = function (params) {
            // estatus de carga
            $scope.cargando = true;

            jsReportSrvc.getReport('SyLv2H6cA', params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                let rango = undefined;

                rango = params.anio;

                saveAs(file, 'Reporte_Vacaciones_' + rango + '.xlsx');

                $scope.cargando = false;
            });
        };
    }]);
}());