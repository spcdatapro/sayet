(function(){

    var rptcompaguactrl = angular.module('cpm.rptcompaguactrl', []);

    rptcompaguactrl.controller('rptComparativoAguaCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', function($scope, empresaSrvc, jsReportSrvc, proyectoSrvc){

        $scope.params = { mes: (moment().month() + 1).toString(), anio: moment().year(), empresas: undefined, proyectos: undefined };
        $scope.content = undefined;
        $scope.empresas = [];
        $scope.proyectos = [];

        empresaSrvc.lstEmpresas().then(function(d){$scope.empresas = d;});

        $scope.loadProyectos = function() {
            proyectoSrvc.lstProyectosPorEmpresa($scope.params.empresas).then(function(d){ $scope.proyectos = d; });
        };

        var test = false;
        $scope.getRptCompAgua = function(){
            $scope.params.empresas = $scope.params.empresas != null && $scope.params.empresas != undefined ? $scope.params.empresas : '';
            $scope.params.proyectos = $scope.params.proyectos != null && $scope.params.proyectos != undefined ? $scope.params.proyectos : '';
            jsReportSrvc.getPDFReport(test ? 'SkvGwSiyQ' : 'rJamEr31m', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());

