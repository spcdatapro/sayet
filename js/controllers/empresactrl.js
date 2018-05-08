(function(){

    var empresactrl = angular.module('cpm.empresactrl', ['cpm.empresasrvc']);

    empresactrl.controller('empresaCtrl', ['$scope', 'empresaSrvc', 'monedaSrvc', 'tipoConfigContaSrvc', 'cuentacSrvc', '$confirm', function($scope, empresaSrvc, monedaSrvc, tipoConfigContaSrvc, cuentacSrvc, $confirm){

        $scope.laEmpresa = {propia: 1};
        $scope.lstEmpresas = [];
        $scope.lasMonedas = [];
        $scope.editando = false;
        $scope.etiqueta = "";
        $scope.laConfConta = {};
        $scope.lasConfsConta = [];
        $scope.lasCtasMov = [];
        $scope.losTiposConf = [];
        $scope.detConf = {};

        monedaSrvc.lstMonedas().then(function(d){
            $scope.lasMonedas = d;
        });

        $scope.prepData = function(d){
            for(var x = 0; x < d.length; x++){
                d[x].id = parseInt(d[x].id);
                d[x].propia = parseInt(d[x].propia);
                d[x].correlafact = parseInt(d[x].correlafact);
                d[x].ultimocorrelativofact = parseInt(d[x].ultimocorrelativofact);
                d[x].fechavencefact = moment(d[x].fechavencefact).isValid() ? moment(d[x].fechavencefact).toDate() : null;
                d[x].ndplanilla = parseInt(d[x].ndplanilla);
            }
            return d;
        };

        $scope.getLstEmpresas = function(){ empresaSrvc.lstEmpresas().then(function(d){ $scope.lstEmpresas = $scope.prepData(d); }); };

        $scope.resetEmpresa = function(){
            $scope.laEmpresa = {
                id: 0, nomempresa: null, abreviatura: null, propia: 1, nit: null, direccion: null, idmoneda: '1', seriefact: null, correlafact: null, fechavencefact: null, ultimocorrelativofact: null
            };
        };

        $scope.getEmpresa = function(idempresa){
            empresaSrvc.getEmpresa(+idempresa).then(function(d){
                $scope.laEmpresa = $scope.prepData(d)[0];
                $scope.getConfigConta($scope.laEmpresa);
            });
        };

        $scope.setData = function(obj){
            obj.propia = obj.propia !== null && obj.propia != undefined ? obj.propia : 0;
            obj.seriefact = obj.seriefact != null && obj.seriefact != undefined ? obj.seriefact : '';
            obj.correlafact = obj.correlafact != null && obj.correlafact != undefined ? obj.correlafact : 0;
            obj.fechavencefactstr = moment(obj.fechavencefact).isValid() ? moment(obj.fechavencefact).format('YYYY-MM-DD') : '';
            obj.ultimocorrelativofact = obj.ultimocorrelativofact != null && obj.ultimocorrelativofact != undefined ? obj.ultimocorrelativofact : 0;
            obj.ndplanilla = obj.ndplanilla != null && obj.ndplanilla != undefined ? obj.ndplanilla : 0;
            return obj;
        };

        $scope.addEmpresa = function(obj){
            obj = $scope.setData(obj);
            empresaSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstEmpresas();
                $scope.getEmpresa(d.lastid);
            });
        };

        $scope.updEmpresa = function(obj){
            obj = $scope.setData(obj);
            empresaSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstEmpresas();
                $scope.getEmpresa(obj.id);
            });
        };

        $scope.delEmpresa = function(id){
            empresaSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstEmpresas();
            });
        };

        $scope.getLstConfigConta = function(idempresa){
            empresaSrvc.lstConfigConta(idempresa).then(function(det){ $scope.lasConfsConta = det; });
        };

        $scope.getConfigConta = function (objEmpresa) {
            $scope.editando = true;
            $scope.etiqueta = objEmpresa;
            tipoConfigContaSrvc.lstTiposConfigConta().then(function(d){ $scope.losTiposConf = d; });
            cuentacSrvc.getByTipo(parseInt(objEmpresa.id), 0).then(function(d){ $scope.lasCtasMov = d; });
            $scope.getLstConfigConta(parseInt(objEmpresa.id));
            goTop();
        };

        $scope.addConfCont = function(obj){
            obj.idempresa = parseInt($scope.etiqueta.id);
            obj.idtipoconfig = parseInt(obj.objTipoConf.id);
            obj.idcuentac = parseInt(obj.objCuenta[0].id);
            empresaSrvc.editRow(obj, 'cc').then(function(){
                $scope.getLstConfigConta(parseInt($scope.etiqueta.id));
                $scope.detConf = {};
                $scope.searchcta = "";
            });
        };

        $scope.delConfConta = function(idconf){
            $confirm({text: '¿Seguro(a) de eliminar esta configuración?', title: 'Eliminar configuración contable', ok: 'Sí', cancel: 'No'}).then(function() {
                empresaSrvc.editRow({id:idconf}, 'dc').then(function(){ $scope.getLstConfigConta($scope.etiqueta.id); });
            });
        };

        $scope.getLstEmpresas();
        $scope.resetEmpresa();

    }]);

}());

