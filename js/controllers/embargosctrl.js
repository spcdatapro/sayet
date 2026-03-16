(function () {

    var controller = angular.module('cpm.embargos', []);

    controller.controller('embargosCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'authSrvc', 'empleadoSrvc', '$filter', function ($scope,
        jsReportSrvc, empresaSrvc, authSrvc, empleadoSrvc, $filter) {

        // variables para selectores
        $scope.empresas = [];
        $scope.empleados = [];
        $scope.cargando = true;

        // parametros lista
        $scope.lista = { ver: '1' };

        // parametros Proyección Embargos
        $scope.proy = {};

        // estatus de carga
        $scope.cargando = false;

        // para visualizaciones en pantalla
        $scope.content = `${window.location.origin}/blank.html`

        // traer empresas
        authSrvc.getSession().then(usr => {
            if (parseInt(usr.workingon) > 0) {
                $scope.lista.idempresa = usr.workingon.toString();
            }
        })

        empresaSrvc.lstEmpresas().then(d => $scope.empresas = d);
        empleadoSrvc.lstEmpleados().then(d => $scope.empleados = d);

        //pdf proyeccion embargos
        $scope.getPDF = function (params, id) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getPDFReport(id, params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };

        // excel proyeccion embargos
        $scope.getXML = function (params, id, tipo) {
            // estatus de carga
            $scope.cargando = true;

            try {
                jsReportSrvc.getReport(id, params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let nombre = params.idempleado > 0 ? ($filter('getById')($scope.empleados, params.idempleado)).nombre : ($filter('getById')($scope.empresas, params.idempresa)).nomempresa;

                    saveAs(file, 'Reporte' + tipo + nombre + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                console.log(err);
                $scope.cargando = false;
            }
        };
    }]);
}());
