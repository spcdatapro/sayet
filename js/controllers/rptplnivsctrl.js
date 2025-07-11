(function(){

    const rptplnivsctrl = angular.module('cpm.rptplnivsctrl', []);

    rptplnivsctrl.controller('rptPlnIVSCtrl', ['$scope', 'jsReportSrvc', 'empServicios', ($scope, jsReportSrvc, empServicios) => {

        $scope.params = { idempleado: undefined, del: moment().startOf('year').toDate(), al: moment().endOf('month').toDate() };
        $scope.empleados = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        empServicios.buscar({'sin_limite':1}).then((res) => {
            res.resultados.forEach(value => {
                value.segundonombre = value.segundonombre ? value.segundonombre : '';
                value.tercernombre = value.tercernombre ? value.tercernombre : '';

                value.nombre = value.primernombre + ' ' + value.segundonombre + ' ' + value.tercernombre;

                value.primerapellido = value.primerapellido ? value.primerapellido : '';
                value.segundoapellido = value.segundoapellido ? value.segundoapellido : '';
                value.apellidocasada = value.apellidocasada ? value.apellidocasada : '';

                value.apellidos = value.primerapellido + ' ' + value.segundoapellido + ' ' + value.apellidocasada;
            });
            $scope.empleados = res.resultados
        });

        const test = false;
        $scope.getReporte = () => {
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            // console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'rk3SeDokw' : 'rk3SeDokw', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };
    }]);
}());
