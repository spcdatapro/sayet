(function(){

    var beneficiarioctrl = angular.module('cpm.beneficiarioctrl', []);

    beneficiarioctrl.controller('beneficiarioCtrl', ['$scope', 'beneficiarioSrvc', 'DTOptionsBuilder', 'empresaSrvc', 'cuentacSrvc', 'authSrvc', '$confirm', 'monedaSrvc', '$filter', '$route', function($scope, beneficiarioSrvc, DTOptionsBuilder, empresaSrvc, cuentacSrvc, authSrvc, $confirm, monedaSrvc, $filter, $route){

        $scope.bene = {};
        $scope.beneficiarios = [];
        $scope.editando = false;
        $scope.strBene = '';
        $scope.lasEmpresas = [];
        $scope.objEmpresa = {};
        $scope.monedas = [];
        $scope.dectc = 2;
        $scope.permiso = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true);

        empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.objEmpresa = r[0];
                    $scope.dectc = parseInt($scope.objEmpresa.dectc);
                    monedaSrvc.lstMonedas().then(function(d){
                        $scope.monedas = d;
                        $scope.resetbene();
                    });
                });
            }
        });

        /*
        $scope.$watch('objEmpresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.getLstCuentas();
            }
        });
        */

        $scope.resetbene = function(){
            $scope.bene = { direccion: '', telefono: '', correo: '', concepto: '', tipocambioprov: 1, objMoneda: {} };
            $scope.editando = false;
            $scope.strBene = '';
            monedaSrvc.getMoneda(parseInt($scope.objEmpresa.idmoneda)).then(function(d){
                $scope.bene.objMoneda = d[0];
                $scope.bene.objMoneda.tipocambioprov = parseFloat($scope.bene.objMoneda.tipocambioprov).toFixed($scope.dectc);
                $scope.bene.tipocambioprov = parseFloat(d[0].tipocambio).toFixed($scope.dectc);
            });
        };

        function procData(data){
            for(var i = 0; i < data.length; i++){
                data[i].id = parseInt(data[i].id);
                data[i].idmoneda = parseInt(data[i].idmoneda);
                data[i].tipocambioprov = parseFloat(data[i].tipocambioprov).toFixed($scope.dectc);
            }
            return data;
        }

        $scope.getLstBeneficiarios = function(){ beneficiarioSrvc.lstBeneficiarios().then(function(d){ $scope.beneficiarios = procData(d); }); };

        $scope.getLstDetCuentaC = function(idprov){
            beneficiarioSrvc.lstDetCuentaC(parseInt(idprov)).then(function(det){
                $scope.detContProv = det;
                goTop();
            });
        };

        $scope.getBene = function(idbene){
            beneficiarioSrvc.getBeneficiario(parseInt(idbene)).then(function(d){
                $scope.bene = procData(d)[0];
                $scope.bene.objMoneda = $filter('getById')($scope.monedas, $scope.bene.idmoneda);
                $scope.strBene = 'No. ' + pad($scope.bene.id, 4) + ', ' + $scope.bene.nitnombre;
                $scope.editando = true;
            });
        };

        $scope.addBene = function(obj){
            obj.idmoneda = parseInt(obj.objMoneda.id);
            beneficiarioSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstBeneficiarios();
                $scope.getBene(d.lastid);
            });
        };

        $scope.updBene = function(data, id){
            data.idmoneda = parseInt(data.objMoneda.id);
            beneficiarioSrvc.editRow(data, 'u').then(function(){
                $scope.getLstBeneficiarios();
                $scope.getBene(id);
            });
        };

        $scope.delBene = function(id){
            $confirm({
                text: "¿Seguro(a) de eliminar este beneficiario?",
                title: 'Eliminar beneficiario',
                ok: 'Sí',
                cancel: 'No'})
                .then(function() {
                    //console.log(id);
                    beneficiarioSrvc.editRow({id:id}, 'd').then(function(){
                        $scope.resetbene();
                        $scope.getLstBeneficiarios();
                    });
                });
        };

        $scope.getLstBeneficiarios();

        /*
        $scope.getLstCuentas = function(){
            if($scope.objEmpresa.id !== null && $scope.objEmpresa.id !== undefined){
                cuentacSrvc.getByTipo(parseInt($scope.objEmpresa.id), 0).then(function(d){
                    $scope.lasCuentas = d;
                });
            }
        };

        $scope.addDetProv = function(obj){
            obj.idproveedor = $scope.elProv.id;
            obj.idcuentac = parseInt($scope.elDetContProv.objCuentaC[0].id);
            proveedorSrvc.editRow(obj, 'cd').then(function(){
                $scope.getLstDetCuentaC($scope.elProv.id);
                $scope.elDetContProv = {};
                $scope.searchcta = "";
            });
        };

        $scope.delDetProv = function(iddetprov){
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                proveedorSrvc.editRow({id:iddetprov}, 'dd').then(function(){ $scope.getLstDetCuentaC($scope.elProv.id); });
            });
        };
        */

    }]);

}());