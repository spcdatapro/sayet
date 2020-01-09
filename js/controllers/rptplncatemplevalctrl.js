(function(){

    var rptplncatemplevalctrl = angular.module('cpm.rptplncatemplevalctrl', []);

    rptplncatemplevalctrl.controller('rptPlnCatEmpleValCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){

        $scope.params = { minimo: 0.00 };
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        var test = false;
        $scope.getReporte = function(){
            jsReportSrvc.getPDFReport(test ? 'SyuLB8WGN' : 'SyuLB8WGN', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
