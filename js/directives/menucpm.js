(function(){

    var menucpm = angular.module('cpm.menucpm', []);

    menucpm.directive('menuCpm', [function(){
        return {
            restrict: 'E',
            templateUrl: 'templates/menucpm.html',
            replace: true,
            scope:{
                mnuObj: '=',
                objEmp: '=',
                setEmpFunc: '&',
                lstEmp: '=',
                logOutFunc: '&'
            }
        };
    }]);

}());
