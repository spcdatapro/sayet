(function(){

    var rptarchivobictrl = angular.module('cpm.rptarchivobictrl', []);

    rptarchivobictrl.controller('rptArchivoBICtrl', ['$scope', '$window', 'authSrvc', 'empresaSrvc', '$filter', 'planillaSrvc', function($scope, $window, authSrvc, empresaSrvc, $filter, planillaSrvc){

        $scope.params = {fdel: moment().toDate(), fal: moment().toDate(), idempresa: undefined, mediopago: 3};
        $scope.empresas = [];

        /*authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = usrLogged.workingon.toString();
            }
        });*/

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.getArchivoBI = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');

            planillaSrvc.empresas($scope.params).then(function(d){
                for(var i = 0; i < d.length; i++){
                    $scope.params.idempresa = +d[i].idempresa;
                    var idx = $scope.empresas.findIndex(function(emp){ return +emp.id == +$scope.params.idempresa });
                    var nombre = $scope.empresas[idx].abreviatura + 'PLA' + moment($scope.params.fdel).format('DDMMYYYY') + moment($scope.params.fal).format('DDMMYYYY');
                    var qstr = $scope.params.idempresa + '/' + $scope.params.fdelstr + '/' + $scope.params.falstr + '/' + nombre;
                    $window.open('php/generaplnbi.php/gettxt/' + qstr);
                }
            });
        };

    }]);

}());
