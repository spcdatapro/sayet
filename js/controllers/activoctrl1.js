(function(){

    var activoctrl = angular.module('cpm.activoctrl', ['cpm.activosrvc']);

    activoctrl.controller('activoCtrl', ['$scope', 'activoSrvc', 'empresaSrvc','tipoactivoSrvc','activoAdjuntoSrvc','tipoAdjuntoSrvc','Upload','DTOptionsBuilder','municipioSrvc', function($scope, activoSrvc, empresaSrvc, tipoactivoSrvc,activoAdjuntoSrvc,tipoAdjuntoSrvc,Upload,DTOptionsBuilder,municipioSrvc){

        $scope.elActivo = {};
        $scope.lasEmpresas = [];
        $scope.losActivos = [];
        $scope.losTipoActivo = [];
        $scope.elActivoAdjunto = {};
        $scope.losActivosAdjuntos = [];
        $scope.losTipoAdjunto = [];
        $scope.losMunicipios = [];

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap();

        // upload later on form submit or something similar
        $scope.submit = function() {
            if ($scope.file) {
                //console.log($scope.file);
                $scope.upload($scope.file);
            }
        };

        // upload on file select or drop
        $scope.upload = function (file) {
            Upload.upload({
                url: 'php/upload.php',
                method: 'POST',
                file: file,
                sendFieldsAs: 'form',
                fields: {
                    directorio: '../activo_adjunto/',
                    prefijo: 'ACT_'+$scope.elActivo.id+'_'
                }
            }).then(function (resp) {
                //console.log(resp);
                //console.log('Success ' + resp.config.data.file.name + 'uploaded. Response: ' + resp.data);
            }, function (resp) {
                //console.log(resp);
                //console.log('Error status: ' + resp.status);
            }, function (evt) {
                console.log(evt);
                var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                //console.log('progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
            });
        };

        $scope.resetElActivo = function(){
            $scope.elActivo = {
                id : 0,
                idempresa : 0,
                departamento : 0,
                finca : '',
                folio : '',
                libro : '',
                horizontal : 0,
                direccion_cat : '',
                direccion_mun : '',
                iusi : parseFloat(0.0),
                por_iusi : parseFloat(0.0),
                valor_registro : parseFloat(0.0),
                metros_registro : parseFloat(0.0),
                valor_dicabi : parseFloat(0.0),
                metros_dicabi : parseFloat(0.0),
                valor_muni : parseFloat(0.0),
                metros_muni : parseFloat(0.0),
                observaciones : '',
                tipo_activo : 0,
                nombre_corto : '',
                nombre_largo : '',
                zona : 0
            };
        };

        $scope.resetElActivo();

        $scope.getlstEmpresas = function(){
            empresaSrvc.lstEmpresas().then(function(d){
                $scope.lasEmpresas = d;
            });
        };
        $scope.getlstTipoActivo = function() {
            tipoactivoSrvc.lstTipoActivo().then(function (d) {
                $scope.losTipoActivo = d;
            });
        };
        $scope.getlstTipoAdjunto = function() {
            tipoAdjuntoSrvc.lstTipoAdjunto().then(function (d) {
                $scope.losTipoAdjunto = d;
            });
        };
        $scope.getlstMunicipios = function(){
            municipioSrvc.lstMunicipios().then(function(d){
                $scope.losMunicipios = d;
            });
        };

        $scope.getLstActivosAdjuntos = function() {
            activoAdjuntoSrvc.getActivoAdjunto($scope.elActivo.id).then(function(d){
                $scope.losActivosAdjuntos = d;
            });
        };

        $scope.getLstActivos = function(){
            activoSrvc.lstActivo().then(function(d){
                $scope.losActivos = d;
            });
        };

        $scope.$watch('elActivo.file', function () {
            //$scope.upload([$scope.file]);
            //console.log($scope.nuevo);
        });

        $scope.getActivo = function(id){
            activoSrvc.getActivo(id).then(function(d){
                $scope.elActivo = d;
                empresaSrvc.getEmpresa(d.idempresa).then(function(resEmpresa){
                    $scope.elActivo.objEmpresa = resEmpresa[0];
                });

                tipoactivoSrvc.getTipoActivo(d.tipo_activo).then(function(resTipoActivo){
                    $scope.elActivo.objTipoActivo = resTipoActivo[0];
                });

                municipioSrvc.getMunicipio(d.departamento).then(function(resMunicipio){
                    $scope.elActivo.objDepartamento = resMunicipio[0];
                });

                $scope.getlstEmpresas();
                $scope.getlstTipoActivo();
                $scope.getlstMunicipios();
                $scope.getLstActivosAdjuntos()
            });
        };

        $scope.addActivo = function(obj){
            obj.idempresa = $scope.elActivo.objEmpresa.id;
            obj.tipo_activo = $scope.elActivo.objTipoActivo.id;
            obj.departamento = $scope.elActivo.objDepartamento.id;

            activoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstActivos();
                $scope.resetElActivo();
            });

        };

        $scope.updActivo = function(data, id){
            data.id = id;
            data.idempresa = $scope.elActivo.objEmpresa.id;
            data.tipo_activo = $scope.elActivo.objTipoActivo.id;
            data.departamento = $scope.elActivo.objDepartamento.id;

            activoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstActivos();
            });
        };

        $scope.delActivo = function(id){
            activoSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstActivos();
            });
        };

        $scope.addActivoAdjunto = function(obj){
            $scope.submit();
            obj.idactivo = $scope.elActivo.id;
            obj.tipo_adjunto = $scope.elActivoAdjunto.objTipoAdjunto.id;
            obj.ubicacion = "activo_adjunto/"+'ACT_'+$scope.elActivo.id+'_'+$scope.file.name;
            activoAdjuntoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstActivosAdjuntos();
            });

        };
        $scope.updActivoAdjunto = function(data, id){
            data.id = id;
            activoAdjuntoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstActivosAdjuntos();
            });
        };

        $scope.delActivoAdjunto = function(id){
            activoSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstActivosAdjuntos();
            });
        };
        $scope.getScope = function () {
            console.log($scope);
        };

        $scope.getLstActivos();
        $scope.getlstEmpresas();
        $scope.getlstTipoActivo();
        $scope.getlstTipoAdjunto();
        $scope.getlstMunicipios();
    }]);
}());
