(function(){

    var conciliactrl = angular.module('cpm.conciliactrl', ['cpm.tranbacsrvc']);

    conciliactrl.controller('conciliaCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder){

        $scope.laEmpresa = {};
        $scope.lasEmpresas = [];
        $scope.losBancos = [];
        $scope.elBanco = {};
        $scope.lasTran = [];
        $scope.afecha = moment().toDate();
        $scope.fechaconcilia = moment().toDate();
        $scope.qver = 0;

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true);

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.lasEmpresas = d;
        });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.laEmpresa = d[0];
                    $scope.getLstBancos();
                });
            }
        });

        $scope.getLstBancos = function(){
            bancoSrvc.lstBancosActivos(parseInt($scope.laEmpresa.id)).then(function(r){
                $scope.losBancos = r;
                $scope.lasTran = [];
            });
        };

        $scope.getLstTran = function(){
            if($scope.elBanco !== null && $scope.elBanco !== undefined){
                $scope.qver = $scope.qver != null && $scope.qver != undefined ? $scope.qver : 0;
                tranBancSrvc.lstAConciliar($scope.elBanco.id, (moment($scope.afecha).isValid ? moment($scope.afecha).format('YYYY-MM-DD') : '0'), $scope.qver).then(function(d){
                    $scope.lasTran = d;
                    for(var i = 0; i < $scope.lasTran.length; i++){
                        $scope.lasTran[i].fecha = moment($scope.lasTran[i].fecha).toDate();
                        $scope.lasTran[i].numero = parseInt($scope.lasTran[i].numero);
                        $scope.lasTran[i].monto = parseFloat($scope.lasTran[i].monto);
                        $scope.lasTran[i].operado = parseInt($scope.lasTran[i].operado) === 1;
                    }
                });
            }
        };

        $scope.updOperado = function(data, id){
            data.operado = data.operado ? 0 : 1;
            //console.log(data);
            tranBancSrvc.editRow({id: data.id, operado: data.operado, foperado: moment($scope.fechaconcilia).format('YYYY-MM-DD')}, 'o').then(function(){ $scope.getLstTran(); });
        };

    }]);

}());
