(function(){

    var genpagosplnctrl = angular.module('cpm.genpagosplnctrl', []);

    genpagosplnctrl.controller('genPagosPlnCtrl', ['$scope', '$window', 'authSrvc', 'empresaSrvc', '$filter', 'planillaSrvc', 'jsReportSrvc', '$uibModal', function($scope, $window, authSrvc, empresaSrvc, $filter, planillaSrvc, jsReportSrvc, $uibModal){

        $scope.params = {fdel: moment().toDate(), fal: moment().toDate(), idempresa: undefined, mediopago: null};
        $scope.empresas = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        $scope.genND = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.mediopago = 3;

            planillaSrvc.empresas($scope.params).then(function(d){
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalDatosND.html',
                    controller: 'modalDatosND',
                    resolve:{
                        empresas: function(){ return d; },
                        params: function(){ return $scope.params; }
                    }
                });

                modalInstance.result.then(function(){}, function(){ return 0; });
            });
        };

        $scope.genChq = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.mediopago = 1;

            planillaSrvc.empresas($scope.params).then(function(d){
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalDatosChq.html',
                    controller: 'modalDatosChq',
                    resolve:{
                        empresas: function(){ return d; },
                        params: function(){ return $scope.params; }
                    }
                });

                modalInstance.result.then(function(){}, function(){ return 0; });
            });
        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    genpagosplnctrl.controller('modalDatosND', ['$scope', '$uibModalInstance', 'empresas', 'params', '$confirm', 'jsReportSrvc', '$window', 'tranBancSrvc', 'planillaSrvc', function($scope, $uibModalInstance, empresas, params, $confirm, jsReportSrvc, $window, tranBancSrvc, planillaSrvc){
        $scope.empresas = empresas;
        $scope.params = params;

        $scope.existeND = function(idx, idbanco, numero){
            tranBancSrvc.existe({idbanco: idbanco, tipotrans: 'B', numero: +numero}).then(function(d){
                if(+d.existe == 1){
                    $confirm({text: 'La nota de débito No. ' + numero + ' ya existe en este banco. ¿Desea probar otro número? (click en "No" para mostrar los datos ya generados)',
                        title: 'Generación de notas de débito', ok: 'Sí', cancel: 'No'}).then(function() {
                        $scope.empresas[idx].ndplanilla = null;
                    });
                }
            });
        };

        $scope.ok = function () {
            $confirm({text: 'Esto generará las notas de débito con los datos seleccionados. ¿Seguro(a) de continuar?', title: 'Generación de notas de débito', ok: 'Sí', cancel: 'No'}).then(async function() {
                var test = false, parametros = {fdelstr: $scope.params.fdelstr, falstr: $scope.params.falstr, idempresa: null, idbanco: null, notadebito: null};
                // console.log($scope.empresas);
                for(var i = 0; i < $scope.empresas.length; i++){
                    parametros.idempresa = +$scope.empresas[i].idempresa;
                    parametros.notadebito = $scope.empresas[i].ndplanilla;
                    parametros.idbanco = +$scope.empresas[i].idbanco;
                    //console.log('Parámetros:', parametros);
                    await jsReportSrvc.getPDFReport(test ? 'S10KfLR6M' : 'BJCOcsyCz', parametros).then(function(pdf){ $window.open(pdf); });
                }
                $uibModalInstance.close();
            });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    genpagosplnctrl.controller('modalDatosChq', ['$scope', '$uibModalInstance', 'empresas', 'params', '$confirm', 'jsReportSrvc', '$window', 'tranBancSrvc', 'planillaSrvc', '$uibModal', function($scope, $uibModalInstance, empresas, params, $confirm, jsReportSrvc, $window, tranBancSrvc, planillaSrvc, $uibModal){
        $scope.empresas = empresas;
        $scope.params = params;

        $scope.ok = function () {
            $confirm({text: 'Esto generará los cheques con los datos seleccionados. ¿Seguro(a) de continuar?', title: 'Generación de cheques de empleados', ok: 'Sí', cancel: 'No'}).then(function() {
                planillaSrvc.generachq({fdelstr: $scope.params.fdelstr, falstr: $scope.params.falstr, empresas: $scope.empresas}).then(function(d){
                    var modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: 'modalGenerados.html',
                        controller: 'modalGenerados',
                        windowClass: 'app-modal-window',
                        resolve:{
                            generados: function(){ return d.generados; }
                        }
                    });

                    modalInstance.result.then(function(){ $uibModalInstance.close(); }, function(){ $uibModalInstance.close(); return 0; });

                });
            });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    genpagosplnctrl.controller('modalGenerados', ['$scope', '$uibModalInstance', 'generados', function($scope, $uibModalInstance, generados){
        $scope.generados = generados;

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };
    }]);
}());

