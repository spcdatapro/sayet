(function(){

    var setemprectrl = angular.module('cpm.setempre', []);

    setemprectrl.controller('setEmpreCtrl', ['$scope', '$rootScope', 'empresaSrvc', function($scope, $rootScope, empresaSrvc){

        $scope.lasEmpresas = [];
        $scope.laEmpresa = {};

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.lasEmpresas = d;
        });

        $scope.setWorkingOn = function(){
            $rootScope.workingon = $scope.laEmpresa != null && $scope.laEmpresa != undefined ? $scope.laEmpresa : null;
            //console.log($rootScope.workingon);
        };

    }]);

}());
