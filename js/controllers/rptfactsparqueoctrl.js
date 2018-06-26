(function(){

    var rptfactsparqueoctrl = angular.module('cpm.rptfactsparqueoctrl', []);

    rptfactsparqueoctrl.controller('rptFacturasParqueoCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', function($scope, authSrvc, empresaSrvc, jsReportSrvc, proyectoSrvc){

        $scope.params = { idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), idproyecto: undefined };
        $scope.empresas = [];
        $scope.content = '';
        $scope.proyectos = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.loadProyectos = function(idempresa){
            proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then(function(d){ $scope.proyectos = d; });
        };

        var test = false;
        $scope.getFactsParqueo = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : '';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
            jsReportSrvc.getPDFReport(test ? 'rk4AaQxG7' : 'HJSaHNxfQ', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);

}());