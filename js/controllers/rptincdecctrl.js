(function(){

    var rptincdecctrl = angular.module('cpm.rptincdecctrl', []);

    rptincdecctrl.controller('rptIncDecCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc){

        $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate(), tipo: '1'};
        //$scope.empresas = [];
        //$scope.proyectos = [];
        //empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; $scope.lasEmpresas.push({ id: '', nomempresa: 'Todas las empresas' }); });
        //proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; $scope.proyectos.push({ id:'', nomproyecto: 'Todos los proyectos' });});

        var test = false;
        $scope.getRptIncDec = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'rJ6LbOcGx' : 'Bypkjqczx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate(), tipo: '1'}; };

    }]);
}());
