(function(){

    var rptpagoiusictrl = angular.module('cpm.rptpagoiusictrl', []);

    rptpagoiusictrl.controller('rptPagoIusiCtrl', ['$scope', 'activoSrvc', 'municipioSrvc', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', function($scope, activoSrvc, municipioSrvc, empresaSrvc, jsReportSrvc, authSrvc){

        $scope.losActivos = [];
        $scope.losDeptos = [];
        $scope.objDepto = [];
        $scope.empresas = [];
        $scope.objEmpresa = [];
        $scope.params = {depto: 0, idempresa: 0};
        $scope.data = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        municipioSrvc.lstMunicipios().then(function(d){ $scope.losDeptos = d; });
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
        });

        var test = false;
        $scope.getRepPagosIusi = function(){
            $scope.params.depto = $scope.objDepto[0] != null && $scope.objDepto[0] != undefined ? $scope.objDepto[0].id : 0;
            $scope.params.idempresa = $scope.objEmpresa[0] != null && $scope.objEmpresa[0] != undefined ? $scope.objEmpresa[0].id : 0;
            jsReportSrvc.getPDFReport(test ? 'BkwA9Rpfx' : 'Syti0gDXx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.printVersion = function(){ PrintElem('#toPrint', 'Pagos de IUSI'); };

    }]);
}());
