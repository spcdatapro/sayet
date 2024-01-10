(function () {

    var controller = angular.module('cpm.ivactrl', []);

    controller.controller('ivaCtrl', ['$scope', '$filter', 'compraSrvc', 'authSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'toaster', '$uibModal', '$window', 'jsReportSrvc', function ($scope, $filter, compraSrvc, authSrvc, empresaSrvc, DTOptionsBuilder, toaster, $uibModal, $window, jsReportSrvc) {

        // variables globales
        $scope.compras = [];
        $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), cuales: '0' };
        $scope.sumaiva = 0.00;
        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('ordering', false).withOption('paging', false);
        $scope.compra = {};

        // obtener la sesion del usuario para asignar idempresa
        authSrvc.getSession().then(function (usrLogged) {
            $scope.params.idempresa = usrLogged.workingon;
        });

        $scope.lstIva = function () {
            let params = $scope.params;
            // convertir fechas en formato mysql
            let fdel = moment(params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            let fal = moment(params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';

            // traer compras
            compraSrvc.getComprasIVA(fdel, fal, params.cuales, params.idempresa).then(function (d) {
                $scope.compras = d;
                $scope.sumaiva = d.reduce((accumulator, currentValue) => accumulator + +currentValue.retiva, 0);
            });
        };

        $scope.getCompra = function (idcompra) {
            compraSrvc.getCompra(idcompra).then(function (d) {
                $scope.compra = d[0];
                $scope.modalIva();
            });
        };


        $scope.modalIva = function () {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalIVA.html',
                controller: 'ModalIVA',
                resolve: {
                    compra: function () {
                        return $scope.compra;
                    }
                }
            });

            modalInstance.result.then(function () {
                $scope.lstIva();
            });
        };

        // $scope.printPDF = function () {
        //     var test = false;
        //     $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
        //     $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
        //     $scope.params.isrempleados = $scope.params.isrempleados != null && $scope.params.isrempleados != undefined ? $scope.params.isrempleados : 0.00;
        //     $scope.params.isrcapital = $scope.params.isrcapital != null && $scope.params.isrcapital != undefined ? $scope.params.isrcapital : 0.00;
        //     jsReportSrvc.getPDFReport(test ? '' : 'Syl1vw2K-', $scope.params).then(function (pdf) { $window.open(pdf); });
        // };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    controller.controller('ModalIVA', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc',  function (
        $scope, $uibModalInstance, compra, compraSrvc) {

        // procesar datos
        compra.fechaiva = moment(compra.fechaiva).isValid ? moment(compra.fechaiva).toDate() : undefined;
        compra.mesiva = compra.mesiva > 0 ? +compra.mesiva : undefined; 
        compra.anioiva = compra.anioiva > 0 ? +compra.anioiva : undefined;
        compra.fechastriva = moment(compra.fechaiva).isValid ? moment(compra.fechaiva).format('YYYY-MM-DD') : undefined;

        // asignar compra global igual a copmra
        $scope.compra = compra;

        // variables internas
        let accion = compra.idformiva > 0 ? 'uiva' : 'civa';

        // cuando se modifique la fecha modifiacar anio y mes
        $scope.setMesAnio = function () {
            if (moment($scope.compra.fechaiva).isValid()) {
                $scope.compra.mesiva = moment($scope.compra.fechaiva).month() + 1;
                $scope.compra.anioiva = moment($scope.compra.fechaiva).year();
                $scope.compra.fechastriva = moment($scope.compra.fechaiva).format('YYYY-MM-DD');
            };
        }

        // asiganar id de compra
        $scope.compra.idcompraiva = compra.id;

        $scope.ok = function () {
            // generar formulario de iva y cerrar
            compraSrvc.editRow($scope.compra, accion).then(function () { $uibModalInstance.close(); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);

}());