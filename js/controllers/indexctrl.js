(function(){

    var indexctrl = angular.module('cpm.indexctrl', ['cpm.authsrvc']);

    indexctrl.controller('indexCtrl', ['$scope', '$window', 'authSrvc', function($scope, $window, authSrvc){
        $scope.tituloPagina = 'GCF';
        $scope.auth = {usr: '', pwd: ''};

        $scope.doLogin = function(){
            authSrvc.doLogin($scope.auth).then(function(d){
                if(d[0].logged === true){
                    $window.location.href = 'cpmidx.html';
                }
            });
        };
    }]);

}());
