(function () {

    var tranpagosctrl = angular.module('cpm.tranpagosctrl', ['cpm.tranbacsrvc']);

    tranpagosctrl.controller('tranPagosCtrl', ['$scope', 'tranPagosSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'toaster', 'periodoContableSrvc', function ($scope, tranPagosSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder, toaster, periodoContableSrvc) {

        $scope.objEmpresa = {};
        $scope.losPagos = [];
        $scope.feclimite = moment().toDate();
        $scope.fechatran = moment().toDate();
        $scope.losBancos = [];
        $scope.objBanco = {};
        $scope.esperando = false;
        $scope.qpagos = [];
        $scope.pagosSelected = [];
        $scope.totales = { cantfacts: 0, monto: 0.00 };
        $scope.periodoCerrado = false;

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('ordering', false).withOption('paging', false);

        authSrvc.getSession().then(function (usrLogged) {
            if (parseInt(usrLogged.workingon) > 0) {
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function (d) {
                    $scope.objEmpresa = d[0];
                    $scope.getPagos($scope.objEmpresa.id, null);
                    $scope.loadBancos();
                });
            }
        });

        $scope.loadBancos = function () {
            bancoSrvc.lstBancosActivos($scope.objEmpresa.id).then(function (d) { $scope.losBancos = d; });
        };

        function procDataPagos(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].id = parseInt(data[i].id);
                data[i].idempresa = parseInt(data[i].idempresa);
                data[i].idproveedor = parseInt(data[i].idproveedor);
                data[i].documento = parseInt(data[i].documento);
                data[i].fechapago = moment(data[i].fechapago).toDate();
                data[i].subtotal = parseFloat(data[i].subtotal);
                data[i].totfact = parseFloat(data[i].totfact);
                data[i].montopagado = parseFloat(data[i].montopagado);
                data[i].retenisr = parseInt(data[i].retenisr);
                data[i].pagatodo = parseInt(data[i].pagatodo);
                data[i].montoapagar = parseFloat(data[i].montoapagar);
                data[i].saldo = parseFloat(data[i].saldo);
                data[i].pagar = parseInt(data[i].pagar);
                data[i].isr = parseFloat(data[i].isr);
                data[i].tipocambio = parseFloat(data[i].tipocambio);
                data[i].idmoneda = parseInt(data[i].idmoneda);
            }
            return data;
        }

        $scope.$watch('fechatran', function (newValue, oldValue) {
            if (newValue != null && newValue !== undefined) {
                $scope.chkFechaEnPeriodo(newValue);
            }
        });

        $scope.chkFechaEnPeriodo = function (qFecha) {
            if (angular.isDate(qFecha)) {
                if (qFecha.getFullYear() >= 2000) {
                    periodoContableSrvc.validaFecha(moment(qFecha).format('YYYY-MM-DD')).then(function (d) {
                        var fechaValida = parseInt(d.valida) === 1;
                        if (!fechaValida) {
                            $scope.periodoCerrado = true;
                            //$scope.fechatran = null;
                            toaster.pop({
                                type: 'error', title: 'Fecha de transacción inválida.',
                                body: 'No está dentro de ningún período contable abierto.', timeout: 7000
                            });
                        } else {
                            $scope.periodoCerrado = false;
                        }
                    });
                }
            }
        };

        $scope.getPagos = function (idempresa, bco) {
            $scope.pagosSelected = [];
            const fmoneda = 0;
            if (bco != null && bco != undefined) {
                // fmoneda = parseFloat(bco.tipocambio) > 1 ? parseInt(bco.idmoneda) : 0;
                // fmoneda = parseInt(bco.idmoneda);
            }
            tranPagosSrvc.lstPagos(idempresa, moment($scope.feclimite).format('YYYY-MM-DD'), fmoneda).then(function (d) { $scope.losPagos = procDataPagos(d); });
        };

        $scope.setMontoAPagar = function (obj) {
            if (obj.pagatodo === 1) {
                obj.montoapagar = obj.saldo;
            }
        };

        $scope.chkMontoAPagar = function (obj) {
            if (obj.montoapagar <= 0 || obj.montoapagar > obj.saldo) {
                obj.montoapagar = obj.saldo;
                toaster.pop({
                    type: 'error', title: 'Error en el monto a pagar. Factura ' + obj.serie + ' ' + obj.documento + '.',
                    body: 'El monto a pagar no puede ser cero (0) ni mayor a ' + obj.saldo.toFixed(2), timeout: 7000
                });
            }
        };

        $scope.refrescarInfo = function () {
            $scope.totales = { cantfacts: 0, monto: 0.00 };
            for (var i = 0; i < $scope.losPagos.length; i++) {
                if (+$scope.losPagos[i].pagar === 1) {
                    $scope.totales.cantfacts++;
                    $scope.totales.monto += parseFloat(parseFloat($scope.losPagos[i].montoapagar).toFixed(2));
                }
            }
        };

        $scope.addPagoAGenerar = (pago) => {
            if (+pago.pagar === 1) {
                pago.fechapagostr = moment(pago.fechapago).format('YYYY-MM-DD');
                $scope.pagosSelected.push(pago);
            } else {
                const idx = $scope.pagosSelected.findIndex(p => +p.id === +pago.id);
                if (idx >= 0) {
                    $scope.pagosSelected.splice(idx, 1);
                }
            }
            //console.log(`${moment().format('HH:mm:ss')} = `, $scope.pagosSelected);
        };

        $scope.generaCheques = function (tipo) {
            let temp = [];
            $scope.esperando = true;
            // temp = [];
            temp.push({
                idbanco: parseInt($scope.objBanco.id),
                nombanco: $scope.objBanco.nombre,
                idmoneda: parseInt($scope.objBanco.idmoneda),
                tipocambio: parseFloat($scope.objBanco.tipocambio),
                fechatranstr: moment($scope.fechatran).format('YYYY-MM-DD'),
                tipo: tipo
            });

            /*
            for(var i = 0; i < $scope.losPagos.length; i++){
                if($scope.losPagos[i].pagar === 1){
                    $scope.losPagos[i].fechapagostr = moment($scope.losPagos[i].fechapago).format('YYYY-MM-DD');
                    $scope.qpagos.push($scope.losPagos[i]);
                }
            }
            */

            $scope.qpagos = temp.concat($scope.pagosSelected);
            //console.log('SELECTED = ', $scope.pagosSelected); console.log('PAGOS = ', $scope.qpagos); return;
            if ($scope.qpagos.length > 1) {
                tranPagosSrvc.genPagos($scope.qpagos).then(function (d) {
                    $scope.esperando = false;
                    $scope.qpagos = [];
                    $scope.pagosSelected = [];
                    $scope.getPagos($scope.objEmpresa.id, $scope.objBanco);
                    $scope.loadBancos();
                    toaster.pop({ type: 'info', title: 'Documentos generados.', body: d.mensaje, timeout: 7000 });
                });
            } else {
                $scope.esperando = false;
                toaster.pop({
                    type: 'info', title: 'Información',
                    body: 'Para poder generar documentos, seleccione una factura con saldo pendiente, por favor.', timeout: 7000
                });
            }

        };
    }]);

}());
