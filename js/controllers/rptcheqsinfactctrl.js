(function(){

    const rptcheqsinfactctrl = angular.module('cpm.rptcheqsinfactctrl', []);

    rptcheqsinfactctrl.controller('rptCheqSinFactCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){

        $scope.params = { fdel: moment().startOf('year').toDate(), fal: moment().toDate() };
        $scope.content = `${window.location.origin}/sayet/blank.html`;  

        
        $scope.getLisCheqFact = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('HkW4xGDgO', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = { fdel: '' , fal: ''}; 
        };

    }]);

}());