(function(){

    var isrctrl = angular.module('cpm.isrctrl', []);

    isrctrl.controller('isrCtrl', ['$scope', '$filter', 'compraSrvc', 'authSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'toaster', '$uibModal', '$window', 'jsReportSrvc', function($scope, $filter, compraSrvc, authSrvc, empresaSrvc, DTOptionsBuilder, toaster, $uibModal, $window, jsReportSrvc){

        $scope.compras = [];
        $scope.compra = {};
        $scope.dectc = 2;
        $scope.params = {idempresa: 0, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), cuales: '0', isrempleados: 0.00, isrcapital: 0.00};
        $scope.sumaisr = 0.00;

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap()
            .withOption('responsive', true)
            .withOption('ordering', false)
            .withOption('paging', false)
        ;

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.dectc = parseInt(d[0].dectc);
                });
            }
        });

        function procDataISR(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].mesisr = parseInt(d[i].mesisr);
                d[i].anioisr = parseInt(d[i].anioisr);
                d[i].totfact = parseFloat(parseFloat(d[i].totfact).toFixed(2));
                d[i].isr = parseFloat(parseFloat(d[i].isr).toFixed(2));
                d[i].isrlocal = parseFloat(parseFloat(d[i].isrlocal).toFixed(2));
                $scope.sumaisr += d[i].isrlocal;
                d[i].tipocambio = parseFloat(parseFloat(d[i].tipocambio).toFixed($scope.dectc));
                d[i].totfactlocal = parseFloat(parseFloat(d[i].totfactlocal).toFixed(2));
                d[i].montobase = parseFloat(parseFloat(d[i].montobase).toFixed(2));
                d[i].iva = parseFloat(parseFloat(d[i].iva).toFixed(2));
            }
            return d;
        }

        $scope.lstIsr = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.isrempleados = $scope.params.isrempleados != null && $scope.params.isrempleados != undefined ? $scope.params.isrempleados : 0.00;
            compraSrvc.editRow($scope.params, 'lstcompisr').then(function(d){
                $scope.compras = procDataISR(d);
            });
        };

        $scope.printPDF = function(){
            var test = false;
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.isrempleados = $scope.params.isrempleados != null && $scope.params.isrempleados != undefined ? $scope.params.isrempleados : 0.00;
			$scope.params.isrcapital = $scope.params.isrcapital != null && $scope.params.isrcapital != undefined ? $scope.params.isrcapital : 0.00;
            jsReportSrvc.getPDFReport(test ? '' : 'Syl1vw2K-', $scope.params).then(function(pdf){ $window.open(pdf); });
        };

        function procDataCompras(data){
            for(var i = 0; i < data.length; i++){
                data[i].documento = parseInt(data[i].documento);
                data[i].mesisr = parseInt(data[i].mesisr);
                data[i].anioisr = parseInt(data[i].anioisr);
                data[i].totfact = parseFloat(parseFloat(data[i].totfact).toFixed(2));
                data[i].isr = parseFloat(parseFloat(data[i].isr).toFixed(2));
                data[i].fechafactura = moment(data[i].fechafactura).toDate();
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed($scope.dectc));
                data[i].fecpagoformisr = moment(data[i].fecpagoformisr).isValid() ? moment(data[i].fecpagoformisr).toDate() : null;
            }
            return data;
        }

        $scope.getCompra = function(idcomp){
            compraSrvc.getCompraISR(idcomp).then(function(d){
                $scope.compra = procDataCompras(d)[0];
                $scope.modalISR();
                //goTop();
            });
        };


        $scope.modalISR = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalISR.html',
                controller: 'ModalISR',
                resolve:{
                    compra: function(){
                        return $scope.compra;
                    }
                }
            });

            modalInstance.result.then(function(idcompra){
                $scope.lstIsr();
            }, function(){ return 0; });
        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    isrctrl.controller('ModalISR', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc', function($scope, $uibModalInstance, compra, compraSrvc){
        $scope.compra = compra;

        $scope.setMesAnio = function(){
            if(moment($scope.compra.fecpagoformisr).isValid()){
                $scope.compra.mesisr = moment($scope.compra.fecpagoformisr).month() + 1;
                $scope.compra.anioisr = moment($scope.compra.fecpagoformisr).year();
            }
        };

        $scope.ok = function () {
            $scope.compra.noformisr = $scope.compra.noformisr != null && $scope.compra.noformisr != undefined ? $scope.compra.noformisr : '';
            $scope.compra.noaccisr = $scope.compra.noaccisr != null && $scope.compra.noaccisr != undefined ? $scope.compra.noaccisr : '';
            $scope.compra.fecpagoformisrstr = moment($scope.compra.fecpagoformisr).isValid() ? moment($scope.compra.fecpagoformisr).format('YYYY-MM-DD') : '';
            $scope.compra.mesisr = $scope.compra.mesisr != null && $scope.compra.mesisr != undefined ? $scope.compra.mesisr : 0;
            $scope.compra.anioisr = $scope.compra.anioisr != null && $scope.compra.anioisr != undefined ? $scope.compra.anioisr : 0;
            compraSrvc.editRow($scope.compra, 'uisr').then(function(){ $uibModalInstance.close($scope.compra.id); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);

}());
