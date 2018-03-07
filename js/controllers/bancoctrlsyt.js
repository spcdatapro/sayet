(function(){

    var bancoctrl = angular.module('cpm.bancoctrl', ['cpm.bancosrvc']);

    bancoctrl.controller('bancoCtrl', ['$scope', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'cuentacSrvc', '$confirm', 'monedaSrvc', function($scope, authSrvc, bancoSrvc, empresaSrvc, cuentacSrvc, $confirm, monedaSrvc){
        //$scope.tituloPagina = 'CPM';

        $scope.elBco = {correlativo: 1};
        $scope.lasEmpresas = [];
        $scope.losBancos = [];
        $scope.lasCuentas = [];
        $scope.lasMonedas = [];

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.lasEmpresas = d;
        });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.elBco.objEmpresa = r[0];
                });
            }
        });

        monedaSrvc.lstMonedas().then(function(d){ $scope.lasMonedas = d; });

        $scope.$watch('elBco.objEmpresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.getLstBancos();
            }
        });

        function procDatos(data){
            for(var i = 0; i < data.length; i++){
                data[i].correlativo = parseInt(data[i].correlativo);
            }
            return data;
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

        $scope.addBanco = function(obj){
            obj.idempresa = $scope.elBco.objEmpresa.id;
            obj.idcuentac = $scope.elBco.objCuentaC.id;
            obj.idmoneda = $scope.elBco.objMoneda.id;
            bancoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstBancos();
                $scope.elBco = {
                    objEmpresa: $scope.elBco.objEmpresa,
                    idempresa: $scope.elBco.objEmpresa.id,
                    objCuentaC: null,
                    objMoneda: null,
                    idcuentac: 0,
                    nombre: '',
                    nocuenta: '',
                    siglas: '',
                    nomcuenta: '',
                    correlativo: 1
                };
            });
        };

        $scope.updBanco = function(data, id){
            data.id = id;
            data.idempresa = $scope.elBco.objEmpresa.id;
            bancoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstBancos();
            });
        };

        $scope.delBanco = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este banco?', title: 'Eliminar Banco', ok: 'Sí', cancel: 'No'}).then(function() {
                bancoSrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstBancos(); });
            });
        };

    }]);

}());
