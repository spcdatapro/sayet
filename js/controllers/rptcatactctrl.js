(function(){

    var rptcatactctrl = angular.module('cpm.rptcatactctrl', []);

    rptcatactctrl.controller('rptCatActCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'tipoactivoSrvc', 'municipioSrvc', function($scope, jsReportSrvc, empresaSrvc, tipoactivoSrvc, municipioSrvc){

        $scope.params = {idempresa:"", idtipo:"", iddepto:"", id:"", ffl:""};
        $scope.empresas = [];
        $scope.tipos = [];
        $scope.deptos = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        tipoactivoSrvc.lstTipoActivo().then(function(d){ $scope.tipos = d; });
        municipioSrvc.lstMunicipios().then(function(d){ $scope.deptos = d; });

        var test = false;

        $scope.getRptCatAct = function(){
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'H1J5Bya7l' : 'H1KeVl67g', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {idempresa:"", idtipo:"", iddepto:"", id:"", ffl:""}; };

    }]);
}());

