(function(){

    var rptactivosctrl = angular.module('cpm.rptactivosctrl', []);

    rptactivosctrl.controller('rptActivosCtrl', ['$scope', 'activoSrvc', 'empresaSrvc','tipoactivoSrvc', 'municipioSrvc', 'localStorageSrvc', '$location', 'jsReportSrvc', function($scope, activoSrvc, empresaSrvc, tipoactivoSrvc, municipioSrvc, localStorageSrvc, $location, jsReportSrvc){


        $scope.lasEmpresas = [];
        $scope.losActivos = [];
        $scope.losTipoActivo = [];
        $scope.params = {idempresa:'', idtipo: '', idmunicipio: ''};
        $scope.data = [];
        $scope.objEmpresa = [];
        $scope.objTipo = [];
        $scope.objMuni = [];
        $scope.municipios = [];
        $scope.empresastr = '';
        $scope.municipiostr = '';
        $scope.tipostr = '';

        empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; });
        tipoactivoSrvc.lstTipoActivo().then(function (d) { $scope.losTipoActivo = d; });
        municipioSrvc.lstMunicipios().then(function(d){ $scope.municipios = d; });

        $scope.setLstEmpresas = function(){ $scope.empresastr = objectPropsToList($scope.objEmpresa, 'nomempresa', ', '); };
        $scope.setLstMunis = function(){ $scope.municipiostr = objectPropsToList($scope.objMuni, 'descripcion', ', '); };
        $scope.setLstTipos = function(){ $scope.tipostr = objectPropsToList($scope.objTipo, 'descripcion', ', '); };

        var test = false;

        $scope.getRptActivos = function(){
            $scope.params.idempresa = objectPropsToList($scope.objEmpresa, 'id', ',');
            $scope.params.idtipo = objectPropsToList($scope.objTipo, 'id', ',');
            $scope.params.iddepto = objectPropsToList($scope.objMuni, 'id', ',');
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'Bk4-helgx' : 'ryRNMROex', $scope.params).then(function(pdf){ $scope.content = pdf; });
            //activoSrvc.rptActivos($scope.params).then(function(d){ $scope.losActivos = d; $scope.styleData(); });
        };

    }]);
}());
