(function(){

    const testctrl = angular.module('cpm.testctrl', []);

    testctrl.controller('testCtrl', ['$scope', 'testSrvc', ($scope, testSrvc) => {

        $scope.deptos = [];

        $scope.getGacelaDeptos = () => {
            testSrvc.gacelaDepto().then(d => {
                $scope.deptos = d;
                console.log('DEPTOS = ', $scope.deptos);
            });
        };


        $scope.getGacelaDeptos();

    }]);

}());
