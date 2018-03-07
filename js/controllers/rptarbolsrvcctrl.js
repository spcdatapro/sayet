(function(){
    
        var rptarbolsrvcctrl = angular.module('cpm.rptarbolsrvcctrl', []);
    
        rptarbolsrvcctrl.controller('rptArbolSrvcCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){
    
            $scope.params = {mes: (moment().month() + 1).toString(), anio: moment().year()};
            $scope.content = undefined;
    
            var test = false;
            $scope.getRptAgua = function(){
                jsReportSrvc.getPDFReport(test ? '' : 'HJ5eACWtZ', $scope.params).then(function(pdf){ $scope.content = pdf; });
            };
    
            $scope.resetParams = function(){ $scope.params = {mes: (moment().month() + 1).toString(), anio: moment().year()}; };
    
        }]);
    }());
    