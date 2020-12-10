(function () {

    const trangenchqotscontctrl = angular.module('cpm.trangenchqotscontctrl', []);

trangenchqotscontctrl.controller('tranGenChqOtsContadoCtrl', ['$scope', 'presupuestoSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'toaster', 'periodoContableSrvc', '$http', '$window', '$q', ($scope, presupuestoSrvc, authSrvc, bancoSrvc, empresaSrvc, toaster, periodoContableSrvc, $http, $window, $q) => {

    $scope.objEmpresa = {};
    $scope.losPagos = [];
    $scope.losBancos = [];
    $scope.objBanco = {};
    $scope.qpagos = [];
    $scope.totales = { cantots: 0, monto: 0.00 };
    $scope.periodoCerrado = false;
    $scope.fechatran = moment().toDate();
    $scope.empresas = [];
    $scope.generarTodos = 0;
    $scope.presupuesto = {};
    $scope.ot = {};
    $scope.search = '';

    authSrvc.getSession().then(async (usrLogged) => {
        await $scope.esDePresupuesto();
        if (parseInt(usrLogged.workingon) > 0) {
            empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then((d) => {
                $scope.objEmpresa = d[0];
                $scope.getPagos();
                $scope.loadBancos();
            });
        }
    });

    $scope.esDePresupuesto = async () => {
        if (+$scope.idot > 0 && !$scope.ot.id) {
            await presupuestoSrvc.getOt($scope.idot).then(d => { $scope.ot = d[0]; });
            await presupuestoSrvc.getPresupuesto($scope.ot.idpresupuesto).then(d => {
                $scope.presupuesto = d[0];
                $scope.search = $scope.presupuesto.id;
            });
        }
    };

    $scope.loadBancos = function () {
        bancoSrvc.lstBancosActivos(null).then(function (d) { 
            d.map(bco => {
                bco.id = +bco.id;
                bco.idempresa = +bco.idempresa;
                return bco;
            });
            $scope.losBancos = d; 
            //console.log($scope.losBancos);
        });
    };

    function procDataPagos(data) {
        for (let i = 0; i < data.length; i++) {
            data[i].id = +data[i].id;
            data[i].generar = +data[i].generar;
            data[i].idempresa = +data[i].idempresa;
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
                    const fechaValida = parseInt(d.valida) === 1;
                    if (!fechaValida) {
                        $scope.periodoCerrado = true;
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

    $scope.getPagos = () => {
        presupuestoSrvc.lstPagosPendOtContado().then((d) => {                
            if(d.empresas.length > 0) {
                d.empresas.map(e => {
                    e.idempresa = +e.idempresa;
                    return e;
                });
            }
            //console.log(d.empresas);
            $scope.empresas = d.empresas;
            $scope.losPagos = procDataPagos(d.pagos);
        });
    };

    $scope.setBanco = (idempresa, idbanco) => $scope.losPagos.map((p) => p.idbanco = +p.idempresa === +idempresa ? idbanco : p.idbanco);
    $scope.setGenerar = () => $scope.losPagos.map((p) => p.generar = $scope.generarTodos);

    $scope.addPagoAGenerar = (pago) => {
        if (+pago.generar === 1 && +pago.idbanco > 0) {
            $scope.qpagos.push({
                idpago: pago.id,
                idbanco: pago.idbanco,
                idot: +pago.id
            });
        } else if (+pago.generar === 0 && +pago.idbanco > 0) {
            const idx = $scope.qpagos.findIndex(p => +p.idpago === +pago.id && +p.idbanco === +pago.idbanco);
            if (idx >= 0) {
                $scope.qpagos.splice(idx, 1);
            }
        } else {
            toaster.pop({
                type: 'warning',
                title: 'Generación de pagos de OTs',
                body: 'Faltan datos para poder agregar el pago para la generación del cheque.',
                timeout: 3000
            });
            pago.generar = 0;
        }
    }

    $scope.generaCheques = (tipo) => {            
        //console.log($scope.qpagos); return;
        if ($scope.qpagos.length > 0) {
            presupuestoSrvc.editRow({
                fecha: moment($scope.fechatran).format('YYYY-MM-DD'),
                tipo: tipo,
                pagos: $scope.qpagos
            }, 'genpagoscontado').then((d) => {
                if (d.segeneraron) {
                    toaster.pop({
                        type: 'success',
                        title: 'Generación de pagos de OTs',
                        body: `Se generaron los pagos No. ${d.cheques}`,
                        timeout: 3000
                    });
                    // $scope.printOts($scope.qpagos);
                } else {
                    toaster.pop({
                        type: 'error',
                        title: 'Generación de pagos de OTs',
                        body: 'Hubo un error en la generación de pagos.',
                        timeout: 3000
                    });
                }
                $scope.qpagos = [];
                $scope.getPagos();
                $scope.loadBancos();
            });
        }
    };

    /*
    $scope.printOts = (generados) => {
        const url = window.location.origin + ':5489/api/report';
        let props = {}, file, formData = new FormData();

        const promises = generados.map(generado => {
            props = { 'template':{'shortid': 'S1eAuyN2b'}, 'data': { idot: generado.idot } };
            return $http.post(url, props, {responseType: 'arraybuffer'});
        });

        $q.all(promises).then((respuestas) => {
            for(let i = 0; i < generados.length; i++){
                file = new Blob([respuestas[i].data], {type: 'application/pdf'});
                formData.append(`OT_${+generados[i].idot}`, file);
            }

            $.ajax({
                url : "php/rptotgroup.php",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: () => { },
                error: () => console.log("Se produjo un error al generar la impresión de OTs...")
            }).done(() => {
                const urlpdf = window.location.origin + '/sayet/php/pdfgenerator/OTs.pdf';
                $window.open(urlpdf);
            });
        });
    }
    */

}]);

}());