(function(){

    var rptasistelibrosctrl = angular.module('cpm.rptasistelibrosctrl', []);

    rptasistelibrosctrl.controller('rptAsisteLibrosCtrl', ['$scope', '$window', 'authSrvc', 'empresaSrvc', '$filter', function($scope, $window, authSrvc, empresaSrvc, $filter){

        $scope.params = {mes: (moment().month() + 1), anio: moment().year(), idempresa: undefined, establecimiento: 1};
        $scope.empresas =[];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = usrLogged.workingon.toString();
            }
        });

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.getAsisteLibros = function(){
            var nombre = 'ASL' + $filter('padNumber')($scope.params.mes, 2) + $scope.params.anio + moment().format('DDMMYYYYhhmmss');
            var qstr = $scope.params.establecimiento + '/' + $scope.params.idempresa + '/' + $scope.params.mes + '/' + $scope.params.anio + '/' + nombre;
            $window.open('php/rptasistelibros.php/gettxt/' + qstr);
        };

    }]);

}());