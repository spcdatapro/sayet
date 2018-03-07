(function(){

    var municipioctrl = angular.module('cpm.municipioctrl', []);

    municipioctrl.controller('municipioCtrl', ['$scope', 'municipioSrvc', '$confirm', function($scope, municipioSrvc, $confirm){

        $scope.elMuni = {codigo: '', depto:0, habilitado: 0};
        $scope.municipios = [];

        $scope.getLstAllMunicipios = function(){
            municipioSrvc.lstAllMunicipios().then(function(d){
                for(var x = 0; x < d.length; x++){
                    d[x].id = parseInt(d[x].id);
                    d[x].depto = parseInt(d[x].depto);
                    d[x].habilitado = parseInt(d[x].habilitado);
                };
                $scope.municipios = d;
            });
        };

        $scope.addMuni = function(obj){
            municipioSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstAllMunicipios();
                $scope.elMuni = {habilitado: 0};
            });
        };

        $scope.updMuni = function(data, id){
            data.id = id;
            municipioSrvc.editRow(data, 'u').then(function(){
                $scope.getLstAllMunicipios();
            });
        };

        $scope.delMuni = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este municipio?', title: 'Eliminar municipio', ok: 'Sí', cancel: 'No'}).then(function() {
                municipioSrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstAllMunicipios(); });
            });
        };

        $scope.getLstAllMunicipios();
    }]);

}());
