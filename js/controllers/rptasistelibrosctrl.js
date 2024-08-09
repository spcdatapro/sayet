(function(){

    var rptasistelibrosctrl = angular.module('cpm.rptasistelibrosctrl', []);

    rptasistelibrosctrl.controller('rptAsisteLibrosCtrl', ['$scope', '$window', 'authSrvc', 'empresaSrvc', '$filter', function($scope, $window, authSrvc, empresaSrvc, $filter){

        $scope.params = {mes: (moment().month() + 1), anio: moment().year(), idempresa: undefined, establecimiento: 1};
        $scope.empresas =[];

        authSrvc.getSession().then(function(usrLogged){
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = usrLogged.workingon.toString();
            }
        });

        $scope.getAsisteLibros = function(){
            var nombre = 'ASL' + $filter('padNumber')($scope.params.mes, 2) + $scope.params.anio + moment().format('DDMMYYYYhhmmss');
            var qstr = $scope.params.establecimiento + '/' + $scope.params.idempresa + '/' + $scope.params.mes + '/' + $scope.params.anio + '/' + nombre;
            $window.open('php/rptasistelibros.php/gettxt/' + qstr);
        };

    }]);

}());