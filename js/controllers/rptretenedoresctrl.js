(function(){

    var rptretenedoresctrl = angular.module('cpm.rptretenedoresctrl', []);

    rptretenedoresctrl.controller('rptRetenedoresCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){

        var test = false;
        $scope.getRepRetenedores = function(){
            jsReportSrvc.getPDFReport(test ? 'HJQsry5Lf' : 'Hy0_pJcIM', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getRepRetenedores();

    }]);
}());
