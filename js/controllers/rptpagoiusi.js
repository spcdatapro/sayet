(function(){

    var rptpagoiusictrl = angular.module('cpm.rptpagoiusictrl', []);

    rptpagoiusictrl.controller('rptPagoIusiCtrl', ['$scope', 'activoSrvc', 'municipioSrvc', 'empresaSrvc', 'jsReportSrvc', function($scope, activoSrvc, municipioSrvc, empresaSrvc, jsReportSrvc){

        $scope.losActivos = [];
        $scope.losDeptos = [];
        $scope.objDepto = [];
        $scope.empresas = [];
        $scope.objEmpresa = [];
        $scope.params = {depto: 0, idempresa: 0};
        $scope.data = [];

        municipioSrvc.lstMunicipios().then(function(d){ $scope.losDeptos = d; });
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        var test = false;
        $scope.getRepPagosIusi = function(){
            $scope.params.depto = $scope.objDepto[0] != null && $scope.objDepto[0] != undefined ? $scope.objDepto[0].id : 0;
            $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;
            jsReportSrvc.getPDFReport(test ? 'BkwA9Rpfx' : 'Syti0gDXx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.printVersion = function(){ PrintElem('#toPrint', 'Pagos de IUSI'); };

    }]);
}());
