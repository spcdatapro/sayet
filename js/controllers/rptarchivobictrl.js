(function(){

    var rptarchivobictrl = angular.module('cpm.rptarchivobictrl', []);

    rptarchivobictrl.controller('rptArchivoBICtrl', ['$scope', '$window', 'authSrvc', 'empresaSrvc', '$filter', function($scope, $window, authSrvc, empresaSrvc, $filter){

        $scope.params = {fdel: moment().toDate(), fal: moment().toDate(), idempresa: undefined};
        $scope.empresas =[];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = usrLogged.workingon.toString();
            }
        });

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.getArchivoBI = function(){

            var idx = $scope.empresas.findIndex(function(emp){ return +emp.id == +$scope.params.idempresa });

            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            var nombre = $scope.empresas[idx].abreviatura + 'PLA' + moment($scope.params.fdel).format('DDMMYYYY') + moment($scope.params.fal).format('DDMMYYYY');
            var qstr = $scope.params.idempresa + '/' + $scope.params.fdelstr + '/' + $scope.params.falstr + '/' + nombre;
            $window.open('php/generaplnbi.php/gettxt/' + qstr);
        };

    }]);

}());
