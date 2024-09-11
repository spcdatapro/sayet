(function () {

    var controller = angular.module('cpm.antiguedad', []);

    controller.controller('rptAntiguedad', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc', 'proyectoSrvc', function ($scope, jsReportSrvc, empresaSrvc, authSrvc, proyectoSrvc) {

        // variables para selectores
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.empleados = [];
        $scope.usuario = {};
        $scope.proyectos = [];

        // estatus de carga
        $scope.cargando = false;

        // parametros para reporte
        $scope.params = { agrupar: '1', fal: moment().toDate() };

        // para visualizaciones en pantalla
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        authSrvc.getSession().then(function (usuario) {
            $scope.usuario = usuario;
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function (d) {
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    $scope.empresas = d;
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

            params.falstr = moment(params.fal).format('YYYY-MM-DD');

            jsReportSrvc.getPDFReport('r1yLuvkpA', params).then(function (pdf) {
                $scope.content = pdf;
                $scope.cargando = false;
            });
        };

        // excel
        $scope.getXML = function (params) {
            // estatus de carga
            $scope.cargando = true;

            params.falstr = moment(params.fal).format('YYYY-MM-DD');

            jsReportSrvc.getReport('H1iI_w1pC', params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                let rango = undefined;

                rango = moment(params.fal).format('DD/MM/YYYY');

                saveAs(file, 'Reporte_Antiuedad_Empleados_al_' + rango + '.xlsx');

                $scope.cargando = false;
            });
        };
    }]);
}());