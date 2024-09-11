(function () {

    var controller = angular.module('cpm.antiguedad', []);

    controller.controller('rptAntiguedad', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc', function ($scope, jsReportSrvc, empresaSrvc, authSrvc) {

        // variables para selectores
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.empleados = [];
        $scope.usuario = {};
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // estatus de carga
        $scope.cargando = false;

        // parametros para reporte
        $scope.params = { agrupar: '1', anio: +moment().toDate().getFullYear(), mes: moment().toDate().getMonth().toString() };

        // para visualizaciones en pantalla
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        // authSrvc.getSession().then(function (usuario) {
            // $scope.usuario = usuario;
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function (d) {
                // empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    // let idempresas = [];
                    // autorizado.forEach(aut => {
                        // idempresas.push(aut.id);
                    // });
                    // $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    $scope.empresas = d;
                // });
            });
        // });

        // pdf
        $scope.getPDF = function (params) {
            // estatus de carga
            $scope.cargando = true;

            params.falstr = moment(params.fal).format('YYYY-MM-DD');

            jsReportSrvc.getPDFReport('HkTv0JE3R', params).then(function (pdf) {
                $scope.content = pdf;
                $scope.cargando = false;
            });
        };

        // excel
        $scope.getXML = function (params) {
            // estatus de carga
            $scope.cargando = true;

            jsReportSrvc.getReport('S1btR1E2C', params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                let rango = undefined;

                rango = $scope.meses[params.mes] + ' ' + params.anio;

                saveAs(file, 'Reporte_Prestamos_de_' + rango + '.xlsx');

                $scope.cargando = false;
            });
        };
    }]);
}());