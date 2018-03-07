(function(){

    var rptserviciosctrl = angular.module('cpm.rptserviciosctrl', []);

    rptserviciosctrl.controller('rptServiciosCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'tipoServicioVentaSrvc', function($scope, empresaSrvc, jsReportSrvc, tipoServicioVentaSrvc){

        $scope.empresas = [];
        $scope.tipos = [];
        $scope.params = {idempresa: undefined, idtipo: undefined, verbaja: 0};

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tipos = d; });

        var test = false;
        $scope.getRepServicios = function(){
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
            $scope.params.idtipo = $scope.params.idtipo != null && $scope.params.idtipo != undefined ? $scope.params.idtipo : 0;
            $scope.params.verbaja = $scope.params.verbaja != null && $scope.params.verbaja != undefined ? $scope.params.verbaja : 0;
            jsReportSrvc.getPDFReport(test ? 'ryC3CYwU-' : 'SyEQv5P8b', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
