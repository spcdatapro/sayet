(function(){

    var rptplnhistosueldoctrl = angular.module('cpm.rptplnhistosueldoctrl', []);

    rptplnhistosueldoctrl.controller('rptPlnHistorialSueldoCtrl', ['$scope', 'empleadoSrvc', 'jsReportSrvc', function($scope, empleadoSrvc, jsReportSrvc){

        $scope.params = {
            idempleado: undefined, del: moment().startOf('year').toDate(), al: moment().toDate()
        };
        $scope.empleados = [];

        empleadoSrvc.lstEmpleados().then(function(d){ $scope.empleados = d; });

        var test = false;
        $scope.getReporte = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'HJjxQmyL7' : 'HJjxQmyL7', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
