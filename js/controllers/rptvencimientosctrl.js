(function(){

    var rptvencimientosctrl = angular.module('cpm.rptvencimientosctrl', []);

    rptvencimientosctrl.controller('rptVencimientosCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc){

        $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate()};
        //$scope.empresas = [];
        //$scope.proyectos = [];
        //empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; $scope.lasEmpresas.push({ id: '', nomempresa: 'Todas las empresas' }); });
        //proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; $scope.proyectos.push({ id:'', nomproyecto: 'Todos los proyectos' });});

        var test = false;
        $scope.getRptVenc = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'HJXRWT2fe' : 'ByHigdaMe', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate()}; };

    }]);
}());

