(function () {

    const rptplnivsctrl = angular.module('cpm.rptplnivsctrl', []);

    rptplnivsctrl.controller('rptPlnIVSCtrl', ['$scope', 'jsReportSrvc', 'empleadoSrvc', ($scope, jsReportSrvc, empleadoSrvc) => {

        $scope.paramsMovimientos = { del: moment().startOf('year').toDate(), al: moment().endOf('month').toDate() }
        $scope.empleados = [];
        $scope.content = `${window.location.origin}/blank.html`
        $scope.cargando = false;

        empleadoSrvc.lstEmpleados().then(d => $scope.empleados = d);

        // reporte ivs
        $scope.paramsIVS = { del: moment().startOf('year').toDate(), al: moment().endOf('month').toDate() };

        $scope.getReporteIVS = params => {
            $scope.cargando = true;
            params.fdelstr = moment(params.del).format('YYYY-MM-DD');
            params.falstr = moment(params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('rk3SeDokw', params).then(pdf => {
                $scope.content = pdf;
                $scope.cargando = false;
            })
        }

        $scope.getReporteMovimientos = params => {
            $scope.cargando = true;
            params.fdelstr = moment(params.del).format('YYYY-MM-DD');
            params.falstr = moment(params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('HyIw0Ap9xx', params).then(pdf => { 
                $scope.content = pdf; 
                $scope.cargando = false;
            })
        };

        $scope.getReporteMovXML = params => {
            $scope.cargando = true;
            params.fdelstr = moment(params.del).format('YYYY-MM-DD');
            params.falstr = moment(params.al).format('YYYY-MM-DD');
            jsReportSrvc.getReport('By0sCuusll', $scope.params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });

                let delstr = moment(params.fdelstr).format('DD-MM-YYYY');
                let alstr = moment(params.falstr).format('DD-MM-YYYY');

                let rango = 'Del_' + delstr + '_al_' + alstr;

                saveAs(file, 'Reporte_movimientos_' + rango + '.xlsx');
                $scope.cargando = false;
            })
        }
    }]);
}());