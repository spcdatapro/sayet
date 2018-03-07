(function(){

    var bancoctrl = angular.module('cpm.bancoctrl', ['cpm.bancosrvc']);

    bancoctrl.controller('bancoCtrl', ['$scope', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'cuentacSrvc', '$confirm', 'monedaSrvc', '$route', '$filter', 'tipoImpresionChequeSrvc', function($scope, authSrvc, bancoSrvc, empresaSrvc, cuentacSrvc, $confirm, monedaSrvc, $route, $filter, tipoImpresionChequeSrvc){
        //$scope.tituloPagina = 'CPM';

        $scope.elBco = {correlativo: 1};
        $scope.lasEmpresas = [];
        $scope.losBancos = [];
        $scope.lasCuentas = [];
        $scope.lasMonedas = [];
        $scope.permiso = {};
        $scope.tiposimp = [];

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.lasEmpresas = d;
        });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.elBco.objEmpresa = r[0];
                });
            }
        });

        monedaSrvc.lstMonedas().then(function(d){ $scope.lasMonedas = d; });
        tipoImpresionChequeSrvc.lstTiposImpresionCheque().then(function(d){ $scope.tiposimp = d; });

        $scope.$watch('elBco.objEmpresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.getLstBancos();
            }
        });

        function procDatos(data){
            for(var i = 0; i < data.length; i++){
                data[i].idbanco = parseInt(data[i].idbanco);
                data[i].idcuentac = parseInt(data[i].idcuentac);
                data[i].idempresa = parseInt(data[i].idempresa);
                data[i].idmoneda = parseInt(data[i].idmoneda);
                data[i].correlativo = parseInt(data[i].correlativo);
                data[i].debaja = parseInt(data[i].debaja);
            }
            return data;
        }

        $scope.resetBanco = function(){
            $scope.elBco = {
                objEmpresa: $scope.elBco.objEmpresa, idempresa: $scope.elBco.objEmpresa.id, objCuentaC: null, objMoneda: null, idcuentac: 0, nombre: '',
                nocuenta: '', siglas: '', nomcuenta: '', correlativo: 1, debaja: 0
            };
        };

        $scope.getLstBancos = function(){
            cuentacSrvc.lstCuentasC($scope.elBco.objEmpresa.id).then(function(d){
                $scope.lasCuentas = d;
            });

            monedaSrvc.getMoneda(parseInt($scope.elBco.objEmpresa.idmoneda)).then(function(d){
                $scope.elBco.objMoneda = d[0];
            });

            bancoSrvc.lstBancos($scope.elBco.objEmpresa.id).then(function(d){
                $scope.losBancos = procDatos(d);
            });
        };

        $scope.getBanco = function(idbanco){
            bancoSrvc.getBanco(idbanco).then(function(d){
                $scope.elBco = procDatos(d)[0];
                $scope.elBco.objEmpresa = $filter('getById')($scope.lasEmpresas, $scope.elBco.idempresa);
                $scope.elBco.objCuentaC = $filter('getById')($scope.lasCuentas, $scope.elBco.idcuentac);
                $scope.elBco.objMoneda = $filter('getById')($scope.lasMonedas, $scope.elBco.idmoneda);
                goTop();
            });
        };

        $scope.addBanco = function(obj){
            obj.idempresa = $scope.elBco.objEmpresa.id;
            obj.idcuentac = $scope.elBco.objCuentaC.id;
            obj.idmoneda = $scope.elBco.objMoneda.id;
            obj.debaja = obj.debaja != null && obj.debaja != undefined ? obj.debaja : 0;
            bancoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstBancos();
                $scope.resetBanco();
                goTop();
            });
        };

        $scope.updBanco = function(data, id){
            data.id = id;
            data.idempresa = $scope.elBco.objEmpresa.id;
            data.idcuentac = $scope.elBco.objCuentaC.id;
            data.idmoneda = $scope.elBco.objMoneda.id;
            data.debaja = data.debaja != null && data.debaja != undefined ? data.debaja : 0;
            bancoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstBancos();
                $scope.getBanco(id);
            });
        };

        $scope.delBanco = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este banco?', title: 'Eliminar Banco', ok: 'Sí', cancel: 'No'}).then(function() {
                bancoSrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstBancos(); });
            });
        };

    }]);

}());
