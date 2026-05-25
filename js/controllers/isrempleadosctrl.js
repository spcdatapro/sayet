(function () {

    var controller = angular.module('cpm.isrempleados', []);

    controller.controller('rptIsrEmpleados', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'proyectoSrvc', 'authSrvc', 'empleadoSrvc', '$filter', function ($scope,
        jsReportSrvc, empresaSrvc, proyectoSrvc, authSrvc, empleadoSrvc, $filter) {

        // variables para selectores
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.empleados = [];

        // estatus de carga
        $scope.cargando = false;

        // para visualizaciones en pantalla
        $scope.content = `${window.location.origin}/blank.html`

        // traer empresas
        authSrvc.getSession().then(usr => {
            if (parseInt(usr.workingon) > 0) {
                $scope.global.idempresa = usr.workingon.toString();
            }
        })
        empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });
        empleadoSrvc.lstEmpleados().then(function (d) { $scope.empleados = d; });

        // traer proyectos al cambiar empresa
        $scope.getProyectos = function (idempresa) {
            proyectoSrvc.lstProyectosPorEmpresa(idempresa).then(function (d) { $scope.proyectos = d; });
            $scope.params.idproyecto = undefined;
        };

        // reporte isr

        // parametros
        $scope.isr = { anio: +moment().toDate().getFullYear().toString(), agrupar: '1', mes: moment().toDate().getMonth().toString() };

        // pdf isr
        $scope.getPdfIsr = function (isr) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getPDFReport('rknrmwPzex', isr).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };

        // excel isr 
        $scope.getXmlIsr = function (params) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getReport('HJevmwPGel', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.mes + '_' + params.anio;

                    saveAs(file, 'Reporte_isr_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };

        // fin reporte isr

        // reporte proyeccion global
        // parametros
        $scope.global = { anio: +moment().toDate().getFullYear().toString() };

        // pdf global
        $scope.getPdfGlobal = function (params) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getPDFReport('Syh44TJ-xx', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };

        // excel global 
        $scope.getXmlGlobal = function (params) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getReport('ByRj9pJWee', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;
                    let nombre = ($filter('getById')($scope.empresas, params.idempresa)).nomempresa;

                    saveAs(file, 'Reporte_global_' + nombre + '_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };
        // fin global
        // reporte proyeccion individual

        // parametros
        $scope.individual = { anio: +moment().toDate().getFullYear().toString() };
        // pdf individual
        $scope.getPdfIndividual = function (params) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getPDFReport('H1o8UeKZll', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };

        // excel individual
        $scope.getXmlIndividual = function (params) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getReport('ry43WZFZeg', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;
                    let nombre = ($filter('getById')($scope.empleados, params.idempleado)).nombre;

                    saveAs(file, 'Reporte_proyectado_' + nombre + '_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };
    }]);
}());