(function(){

    var rptaguactrl = angular.module('cpm.rptaguactrl', []);

    rptaguactrl.controller('rptAguaCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', function($scope, empresaSrvc, jsReportSrvc, proyectoSrvc){

        $scope.params = {fvence: moment().toDate(), idempresa: undefined, idproyecto: undefined};
        $scope.content = undefined;
        $scope.empresas = [];
        $scope.proyectos = [];

        empresaSrvc.lstEmpresas().then(function(d){$scope.empresas = d;});

        $scope.loadProyectos = function() {
            proyectoSrvc.lstProyectosPorEmpresa($scope.params.idempresa).then(function(d){ $scope.proyectos = d; });
        };

        var test = false;
        $scope.getRptAgua = function(){
            $scope.params.fvencestr = moment($scope.params.fvence).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : '';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto != undefined ? $scope.params.idproyecto : '';
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'rJG0yZGeb' : 'BJuRszzx-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {fvence: moment().toDate(), idempresa: undefined}; };

    }]);
}());
