(function(){

    var rptcompaguactrl = angular.module('cpm.rptcompaguactrl', []);

    rptcompaguactrl.controller('rptComparativoAguaCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', 'clienteSrvc', 'authSrvc', function($scope, empresaSrvc, jsReportSrvc, proyectoSrvc, clienteSrvc, authSrvc){

        $scope.params = { 
            mes: (moment().month() + 1).toString(), anio: moment().year(), empresas: undefined, proyectos: undefined, cliente: undefined
        };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.lstClientes = [];
        $scope.idusuario = undefined;

        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
            $scope.idusuario = usuario.uid;
        });
        clienteSrvc.lstCliente().then(function(d){$scope.lstClientes = d;});

        $scope.loadProyectos = function() {
            proyectoSrvc.lstProyectosPorEmpresa($scope.params.empresas, $scope.idusuario).then(function(d){ $scope.proyectos = d; });
        };

        var test = false;
        $scope.getRptCompAgua = function(){
            $scope.params.empresas = $scope.params.empresas != null && $scope.params.empresas != undefined ? $scope.params.empresas : '';
            $scope.params.proyectos = $scope.params.proyectos != null && $scope.params.proyectos != undefined ? $scope.params.proyectos : '';
            $scope.params.cliente = $scope.params.cliente !=null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
            jsReportSrvc.getPDFReport(test ? 'SkvGwSiyQ' : 'rJamEr31m', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());

