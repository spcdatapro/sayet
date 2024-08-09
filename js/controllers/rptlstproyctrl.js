(function(){

    var rptlstproyctrl = angular.module('cpm.rptlstproyctrl', []);

    rptlstproyctrl.controller('rptLstProyCtrl', ['$scope', 'empresaSrvc', 'tipoProyectoSrvc', 'proyectoSrvc', 'jsReportSrvc', 'authSrvc', function($scope, empresaSrvc, tipoProyectoSrvc, proyectoSrvc, jsReportSrvc, authSrvc){

        $scope.params = {idempresa: '', idtipo: '', idproyecto: '', detallado: 1};
        $scope.lasEmpresas = [];
        $scope.objEmpresa = [];
        $scope.lsttipos = [];
        $scope.tipo = [];
        $scope.proyectos = [];
        $scope.proyecto = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.lasEmpresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    $scope.lasEmpresas.push({ id: '', nomempresa: 'Todas las empresas' });
                }); 
            });
        });
        tipoProyectoSrvc.lstTipoProyecto().then(function(d){ $scope.lsttipos = d; $scope.lsttipos.push({ id: '', descripcion: 'Todos los tipos' });});
        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; $scope.proyectos.push({ id:'', nomproyecto: 'Todos los proyectos' });});

        var test = false;
        $scope.getRptProyectos = function(){
            if($scope.objEmpresa != null && $scope.objEmpresa != undefined){ $scope.params.idempresa = $scope.objEmpresa.length > 0 ? objectPropsToList($scope.objEmpresa, 'id', ',') : ''; }
            if($scope.tipo != null && $scope.tipo != undefined){ $scope.params.idtipo = $scope.tipo.length > 0 ? objectPropsToList($scope.tipo, 'id', ',') : ''; }
            if($scope.proyecto != null && $scope.proyecto != undefined){ $scope.params.idproyecto = $scope.proyecto.length > 0 ? objectPropsToList($scope.proyecto, 'id', ',') : ''; }
            $scope.params.detallado = $scope.params.detallado != null && $scope.params.detallado != undefined ? $scope.params.detallado : 0;
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'SJiphkrMl' : 'r1A5TESGg', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){
            $scope.params = {idempresa: '', idtipo: '', idproyecto: '', detallado: 1};
            $scope.objEmpresa = [];
            $scope.tipo = [];
            $scope.proyecto = [];
        };

    }]);
}());
