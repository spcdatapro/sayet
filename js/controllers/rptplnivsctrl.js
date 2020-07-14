(function(){

    const rptplnivsctrl = angular.module('cpm.rptplnivsctrl', []);

    rptplnivsctrl.controller('rptPlnIVSCtrl', ['$scope', 'jsReportSrvc', 'empServicios', ($scope, jsReportSrvc, empServicios) => {

        $scope.params = { idempleado: undefined, del: moment().startOf('year').toDate(), al: moment().endOf('month').toDate() };
        $scope.empleados = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        empServicios.buscar({'sin_limite':1}).then((res) => $scope.empleados = res.resultados);

        const test = false;
        $scope.getReporte = () => {
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            // console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'rk3SeDokw' : 'rk3SeDokw', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };
    }]);
}());
