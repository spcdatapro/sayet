(function(){

    var tipocompractrl = angular.module('cpm.tipocompractrl', []);

    tipocompractrl.controller('tipoCompraCtrl', ['$scope', 'tipoCompraSrvc', 'cuentacSrvc', '$confirm', 'authSrvc', '$route', '$filter', function($scope, tipoCompraSrvc, cuentacSrvc, $confirm, authSrvc, $route, $filter){

        $scope.tipocompra = {desctipocompra: '', idcuentac: 0, objCuentaC: undefined, objCuentaCV: undefined};
        $scope.tiposcompra = [];
        $scope.cuentasc = [];
        $scope.idempresa = 0;
        $scope.permiso = {};
        $scope.searchcta = '';

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.idempresa = parseInt(usrLogged.workingon);
                cuentacSrvc.getByTipo($scope.idempresa, 0).then(function(d){ $scope.cuentasc = d; });
            }
        });

        $scope.getLstTiposCompra = function(){
            tipoCompraSrvc.lstTiposCompra().then(function(d){
                for(var i = 0; i < d.length; i++){ d[i].id = parseInt(d[i].id); d[i].idcuentac = parseInt(d[i].idcuentac); }
                $scope.tiposcompra = d;
            });
        };

        $scope.getTipoCompra = function(idtipocompra){
            tipoCompraSrvc.getTipoCompra(idtipocompra).then(function(d){
                for(var i = 0; i < d.length; i++){ d[i].id = parseInt(d[i].id); d[i].idcuentac = parseInt(d[i].idcuentac); }
                $scope.tipocompra = d[0];
                $scope.tipocompra.objCuentaC = [$filter('getById')($scope.cuentasc, $scope.tipocompra.idcuentac)];
                $scope.tipocompra.objCuentaCV = [$filter('getById')($scope.cuentasc, $scope.tipocompra.idcuentacventa)];
                $scope.searchcta = $scope.tipocompra.objCuentaC[0] != null && $scope.tipocompra.objCuentaC[0] != undefined ? $scope.tipocompra.objCuentaC[0].codcta : '';
            });
        };

        $scope.addTipoCompra = function(obj){
            obj.idcuentac = obj.objCuentaC[0].id;
            obj.idcuentacventa = obj.objCuentaCV[0].id;
            tipoCompraSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstTiposCompra();
                $scope.getTipoCompra(parseInt(d.lastid));
                $scope.searchcta = '';
            });
        };

        $scope.updTipoCompra = function(obj){
            obj.idcuentac = obj.objCuentaC[0].id;
            obj.idcuentacventa = obj.objCuentaCV[0].id;
            tipoCompraSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstTiposCompra();
                $scope.getTipoCompra(obj.id);
                $scope.searchcta = '';
            });
        };

        $scope.resetTipoCompra = function(){ $scope.tipocompra = {desctipocompra: '', idcuentac: 0, objCuentaC: undefined, objCuentaCV: undefined}; $scope.searchcta = ''; };

        $scope.delTipoCompra = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este tipo de compra?', title: 'Eliminar tipo de compra', ok: 'Sí', cancel: 'No'}).then(function() {
                tipoCompraSrvc.editRow({id: obj.id}, 'd').then(function(){ $scope.getLstTiposCompra(); $scope.resetTipoCompra(); });
            });
        };

        $scope.getLstTiposCompra();
    }]);

}());
