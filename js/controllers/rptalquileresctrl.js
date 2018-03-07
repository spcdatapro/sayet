(function(){

    var rptalquileresctrl = angular.module('cpm.rptalquileresctrl', []);

    rptalquileresctrl.controller('rptAlquileresCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', 'authSrvc', 'tipoServicioVentaSrvc', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc, authSrvc, tipoServicioVentaSrvc){

        $scope.params = {fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate(), usuario: '', porlocal: 0, sinproy: 0};
        $scope.content = undefined;
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.tipos = [];

        authSrvc.getSession().then(function(usrLogged){ $scope.params.usuario = getIniciales(usrLogged.nombre); });

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tipos = d; });

        var test = false;
        $scope.getRptAlquileres = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.porlocal = $scope.params.porlocal != null && $scope.params.porlocal != undefined ? $scope.params.porlocal : 0 ;
			$scope.params.empresa = $scope.aArreglo($scope.params.empresatmp, 'id'); 
			$scope.params.proyecto = $scope.aArreglo($scope.params.proyectotmp, 'id');
			$scope.params.tipo = $scope.aArreglo($scope.params.tipotmp, 'id');

            var qrep = test ? 'BysL28eNg' : 'BkeNRDgVe';
            if(+$scope.params.sinproy == 1){
                qrep = 'HyZn7Y8RW';
            }

            jsReportSrvc.getPDFReport(qrep, $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = { fdel: moment().startOf('month').toDate() , fal: moment().endOf('month').toDate(), porlocal: 0, sinproy: 0 }; };

        $scope.mostrarProyectos = function() {
            $scope.proyectos = [];

            if ($scope.params.empresatmp) {
                $scope.params.empresatmp.forEach(function(e) {
                    proyectoSrvc.lstProyectosPorEmpresa(e.id).then(function(res){
                        $scope.proyectos = $scope.proyectos.concat(res);
                    });
                });
            }
        }

        $scope.aArreglo = function(a, c) {
            if (a) {
                var tmp = [];

                a.forEach(function(d) { tmp.push(d[c]); });

                return tmp;
            } else {
                return [];
            }
        }

    }]);
}());


