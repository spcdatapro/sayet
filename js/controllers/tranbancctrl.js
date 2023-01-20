(function () {

    var tranbancctrl = angular.module('cpm.tranbancctrl', ['cpm.tranbacsrvc']);

    tranbancctrl.controller('tranBancCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'tipoDocSopTBSrvc', 'tipoMovTranBanSrvc', 'periodoContableSrvc', 'toaster', 'detContSrvc', 'cuentacSrvc', '$confirm', '$filter', '$uibModal', 'razonAnulacionSrvc', 'presupuestoSrvc', 'jsReportSrvc', '$window', 'localStorageSrvc', 'proyectoSrvc', 'socketIOSrvc', 'reciboClientesSrvc', 'tipoCambioSrvc', function ($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder, tipoDocSopTBSrvc, tipoMovTranBanSrvc, periodoContableSrvc, toaster, detContSrvc, cuentacSrvc, $confirm, $filter, $uibModal, razonAnulacionSrvc, presupuestoSrvc, jsReportSrvc, $window, localStorageSrvc, proyectoSrvc, socketIOSrvc, reciboClientesSrvc, tipoCambioSrvc) {

        $scope.laTran = { fecha: new Date(), concepto: '', anticipo: 0, idbeneficiario: 0, tipocambio: parseFloat('1.00000'), esnegociable: 0 };
        $scope.laEmpresa = {};
        $scope.lasEmpresas = [];
        $scope.losBancos = [];
        $scope.lasTran = [];
        $scope.editando = false;
        $scope.strTran = '';
        $scope.fltrtran = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), idbanco: '0', idot: 0 };
        $scope.losDocsSoporte = [];
        $scope.elDocSop = { fechadoc: moment().toDate(), fechaliquida: null };
        $scope.sumaDocsSoporte = 0.00;
        $scope.losTiposDocTB = [];
        $scope.origen = 1;
        $scope.losDetCont = [];
        $scope.elDetCont = { debe: 0.0, haber: 0.0 };
        $scope.origenLiq = 9;
        $scope.liquidacion = [];
        $scope.lasCuentasMov = [];
        $scope.beneficiarios = [];
        $scope.compraspendientes = [];
        $scope.razonesanula = [];
        $scope.dectc = 5;
        $scope.ots = [];
        $scope.compras = [];
        $scope.hayDescuadre = false;
        $scope.noCuadra = false;
        $scope.uid = 0;
        $scope.proyectos = [];
        $scope.recibos = [];
        $scope.recibo = [];
        $scope.selected = {};
        $scope.montoMax = 999999999;
        //$scope.tipotrans = [{value: 'C', text: 'C'}, {value: 'D', text: 'D'}, {value: 'B', text: 'B'}, {value: 'R', text: 'R'}];
        $scope.tipotrans = [];
        $scope.lstndc = [];
        $scope.tipoCambioHoy = {};
        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true);
        $scope.dtOptionsDetCont = DTOptionsBuilder.newOptions().withBootstrap()
            .withBootstrapOptions({
                pagination: {
                    classes: {
                        ul: 'pagination pagination-sm'
                    }
                }
            })
            .withOption('responsive', true)
            .withOption('paging', false)
            .withOption('searching', false)
            .withOption('info', false)
            .withOption('ordering', false)
            .withOption('fnRowCallback', rowCallback);

        $scope.dtOptionsDetContLiquidacion = $scope.dtOptionsDetCont;
        $scope.periodoCerrado = false;
        $scope.presupuesto = {};
        $scope.ot = {};
        $scope.cargando = false;


        //Infinite Scroll Magic
        $scope.infiniteScroll = {};
        $scope.infiniteScroll.numToAdd = 20;
        $scope.infiniteScroll.currentItems = 20;

        $scope.resetInfScroll = function () {
            $scope.infiniteScroll.currentItems = $scope.infiniteScroll.numToAdd;
        };
        $scope.addMoreItems = function () {
            $scope.infiniteScroll.currentItems += $scope.infiniteScroll.numToAdd;
        };

        $scope.ctaContSelected = function (item) {
            //console.log(item);
        };

        empresaSrvc.lstEmpresas().then(function (d) {
            $scope.lasEmpresas = d;
        });

        tipoCambioSrvc.getLastTC().then(function (d) {
            $scope.tipoCambioHoy = +d.lasttc;
        });

        tipoMovTranBanSrvc.lstTiposMovTB().then(function (d) { $scope.tipotrans = d; });
        tranBancSrvc.lstBeneficiarios().then(function (d) { $scope.beneficiarios = d; });
        razonAnulacionSrvc.lstRazones().then(function (d) { $scope.razonesanula = d; });

        authSrvc.getSession().then(async function (usrLogged) {
            $scope.uid = +usrLogged.uid;
            await $scope.esDePresupuesto();
            // console.log($scope.presupuesto);
            usrLogged.workingon = $scope.presupuesto.idempresa || usrLogged.workingon;
            if (parseInt(usrLogged.workingon) > 0) {
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function (d) {
                    $scope.laEmpresa = d[0];
                    $scope.dectc = parseInt(d[0].dectc);
                    $scope.getLstBancos();
                    presupuestoSrvc.lstPagosOt($scope.laEmpresa.id).then(function (d) { $scope.ots = d; });
                    proyectoSrvc.lstProyectosPorEmpresa($scope.laEmpresa.id).then(function (d) { $scope.proyectos = d; });
                    reciboClientesSrvc.lstRecPend($scope.laEmpresa.id).then(function (d) { $scope.recibos = d; });
                });
            }
        });

        $scope.esDePresupuesto = async () => {
            if (+$scope.idot > 0 && !$scope.ot.id) {
                $scope.fltrtran.idot = +$scope.idot;
                await presupuestoSrvc.getOt($scope.idot).then(d => { $scope.ot = d[0]; });
                await presupuestoSrvc.getPresupuesto($scope.ot.idpresupuesto).then(d => { $scope.presupuesto = d[0]; });
            }
        };

        $scope.$watch('laTran.fecha', function (newValue, oldValue) {
            var fecha = newValue;
            if (angular.isDate(fecha)) {
                if (fecha.getFullYear() >= 2000) {
                    fecha = moment(fecha).format('YYYY-MM-DD');
                    periodoContableSrvc.validaFecha(fecha).then(function (d) {
                        var fechaValida = parseInt(d.valida) === 1;
                        if (!fechaValida) {
                            $scope.periodoCerrado = true;
                            toaster.pop({
                                type: 'error', title: 'Fecha de ingreso es inválida.',
                                body: 'No está dentro de ningún período contable abierto.', timeout: 7000
                            });
                        } else {
                            $scope.periodoCerrado = false;
                        }
                    });
                } else {
                    $scope.periodoCerrado = true;
                }
            }
        });

        $scope.getTranInicial = function () {
            var idtranbanini = localStorageSrvc.get('idtranban');
            if (idtranbanini != null && idtranbanini != undefined) {
                localStorageSrvc.clear('idtranban');
                $scope.getDataTran(+idtranbanini);
            }
        };

        $scope.getLstBancos = function () {
            bancoSrvc.lstBancosActivos(parseInt($scope.laEmpresa.id)).then(function (r) {
                $scope.losBancos = r;
                $scope.lasTran = [];
                $scope.getTranInicial();
            });
        };

        function prepareTranBan(d) {
            for (var i = 0; i < d.length; i++) {
                d[i].fecha = moment(d[i].fecha).toDate();
                d[i].numero = parseInt(d[i].numero);
                d[i].monto = parseFloat(d[i].monto);
                d[i].operado = parseInt(d[i].operado);
                d[i].anticipo = parseInt(d[i].anticipo);
                d[i].iddocliquida = parseInt(d[i].iddocliquida);
            }
            return d;
        }

        $scope.getLstTran = function () {
            if ($scope.laTran.objBanco != null && $scope.laTran.objBanco !== undefined) {
                $scope.cargando = true;
                $scope.laTran.tipocambio = parseFloat($scope.laTran.objBanco.tipocambio);
                $scope.cleanInfo();
                $scope.fltrtran.idbanco = $scope.laTran.objBanco.id;
                //console.log($scope.ot);
                if (+$scope.ot.id > 0) {
                    $scope.fltrtran.fdelstr = '';
                    $scope.fltrtran.falstr = '';
                } else {
                    $scope.fltrtran.fdelstr = moment($scope.fltrtran.fdel).format('YYYY-MM-DD');
                    $scope.fltrtran.falstr = moment($scope.fltrtran.fal).format('YYYY-MM-DD');
                }

                $scope.fltrtran.tipotrans = '';
                tranBancSrvc.lstTranFiltr($scope.fltrtran).then(function (d) {
                    $scope.lasTran = prepareTranBan(d);
                    $scope.fltrtran.tipotrans = 'R';
                    tranBancSrvc.lstTranFiltr($scope.fltrtran).then(function (dr) {
                        $scope.lstndc = prepareTranBan(dr);
                        $scope.fltrtran.tipotrans = 'C';
                        tranBancSrvc.lstTranFiltr($scope.fltrtran).then(function (y) {
                            $scope.lstchq = prepareTranBan(y);
                            $scope.fltrtran.tipotrans = '';
                            $scope.cargando = false;
                        });
                    });
                });
            }
        };

        $scope.cleanInfo = function () {
            $scope.laTran.objTipotrans = undefined;
            $scope.laTran.esnegociable = 0;
            $scope.laTran.anticipo = 0;
            $scope.laTran.numero = undefined;
            $scope.laTran.fecha = moment().toDate();
            $scope.laTran.iddetpresup = undefined;
            $scope.laTran.monto = undefined;
            $scope.laTran.objBeneficiario = undefined;
            $scope.laTran.idbeneficiario = 0;
            $scope.laTran.beneficiario = undefined;
            $scope.laTran.concepto = undefined;
            $scope.laTran.iddetpagopresup = undefined;
            $scope.laTran.idproyecto = undefined;
            $scope.laTran.iddocliquida = undefined;
            $scope.laTran.montooriginal = undefined;
            $scope.laTran.retisr = 0;
            $scope.laTran.isr = undefined;
            $scope.laTran.montocalcisr = undefined;
        };

        $scope.resetLaTran = function () {
            $scope.laTran = {
                objBanco: $scope.laTran.objBanco != null && $scope.laTran.objBanco != undefined ? $scope.laTran.objBanco : undefined,
                fecha: moment().toDate(),
                concepto: '',
                anticipo: 0,
                idbeneficiario: 0,
                tipocambio: parseFloat('1'),
                esnegociable: 0,
                iddetpresup: undefined,
                iddetpagopresup: undefined,
                idproyecto: undefined,
                iddocliquida: undefined,
                recibocli: undefined,
                idrecibocli: undefined
            };
            $scope.lasTran = [];
            $scope.lstndc = [];
            $scope.losDocsSoporte = [];
            $scope.elDocSop = { fechadoc: moment().toDate(), fechaliquida: null };
            $scope.losDetCont = [];
            $scope.elDetCont = { debe: 0.0, haber: 0.0 };
            $scope.strTran = '';
            $scope.editando = false;
            $scope.periodoCerrado = false;
        };

        $scope.getNumCheque = function () {
            if ($scope.laTran.objBanco != null && $scope.laTran.objBanco != undefined) {
                if ($scope.laTran.objBanco.id != null && $scope.laTran.objBanco.id != undefined) {
                    if ($scope.laTran.objTipotrans.abreviatura === 'C') {
                        bancoSrvc.getCorrelativoBco(parseInt($scope.laTran.objBanco.id)).then(function (c) { $scope.laTran.numero = parseInt(c[0].correlativo) });
                    } else {
                        $scope.laTran.numero = 0;
                        $scope.laTran.idproyecto = undefined;
                    }
                }
            }
        };

        $scope.setNombreBene = function (bene) {
            if (!$scope.laTran.beneficiario || $scope.laTran.beneficiario.trim() == '') {
                $scope.laTran.beneficiario = bene != null && bene != undefined ? bene.chequesa : '';
            }
        };

        $scope.getDocs = function (td) {
            const idtd = +td.id;
            //console.log(idtd);
            switch (true) {
                case [1, 3].indexOf(idtd) > -1: tranBancSrvc.lstFactCompra($scope.laTran.idbeneficiario, $scope.laTran.id).then(function (d) { $scope.compraspendientes = d; }); break;
                case [2, 4].indexOf(idtd) > -1: tranBancSrvc.lstReembolsos($scope.laTran.idbeneficiario).then(function (d) { $scope.compraspendientes = d; }); break;
            }
        };

        $scope.setData = function (ds) {
            $scope.elDocSop.fechadoc = moment(ds.fechafactura).toDate();
            $scope.elDocSop.serie = ds.serie;
            $scope.elDocSop.documento = ds.documento;
            //$scope.elDocSop.monto = parseFloat(ds.totfact);
            $scope.elDocSop.monto = parseFloat(ds.saldo);

            if (parseFloat($scope.laTran.monto) != parseFloat($scope.elDocSop.monto)) {
                $scope.noCuadra = true;
                toaster.pop({
                    type: 'warning',
                    title: 'Advertencia.',
                    body: 'El monto de la transacción (' + parseFloat($scope.laTran.monto).toFixed(2) +
                        ') no cuadra con el monto del documento de soporte (' + parseFloat($scope.elDocSop.monto).toFixed(2) + ').',
                    timeout: 7000
                });
            }
        };

        $scope.fillData = function (item, model) {
            var tmpObjBene = $filter('filter')($scope.beneficiarios, { id: item.idproveedor, dedonde: item.origenprov }, true);
            $scope.laTran.anticipo = 1;
            $scope.laTran.objBeneficiario = tmpObjBene.length > 0 ? tmpObjBene[0] : undefined;
            $scope.setNombreBene($scope.laTran.objBeneficiario);
            // tranBancSrvc.getMontoOt(item.id).then(d => {
            // $scope.montoMax = d.monto;
            // });

            if (!$scope.laTran.concepto) {
                $scope.laTran.concepto = 'Orden de trabajo ' + item.ot + ' [' + item.notas + ']';
            } else {
                $scope.laTran.concepto = $scope.laTran.concepto + ' / ' + 'Orden de trabajo ' + item.ot + ' [' + item.notas + ']';
            }

            $scope.laTran.tipocambio = parseFloat(item.tipocambio);
        };


        $scope.fillDataOnDocLiq = function (item, model) {
            var tmpObjBene = $filter('filter')($scope.beneficiarios, { id: item.idbeneficiario, dedonde: item.origenbene }, true);
            $scope.laTran.objBeneficiario = tmpObjBene.length > 0 ? tmpObjBene[0] : undefined;
            $scope.laTran.beneficiario = 'Reingreso (' + item.beneficiario + ')';
            $scope.laTran.monto = item.monto;
            $scope.laTran.concepto = 'Reingreso de cheque numero ' + item.numero + ' del proveedor ' + item.beneficiario;
            $scope.laTran.numero = item.numero;
            $scope.laTran.iddetpresup = item.iddetpresup;
            $scope.laTran.tipocambio = item.tipocambio;
            $scope.laTran.anticipo = 1;
            // console.log(item);
        };

        $scope.fillDataOnRecli = function (item) {
            if ($scope.laTran.recibocli.length == 1) {
                $scope.laTran.beneficiario = item.cliente;
                $scope.laTran.monto = item.montorec;
                $scope.laTran.concepto = 'Ingreso recibo clientes ' + item.reccli + '[' + item.concepto + '] Facturas: ' + item.facturas;
            } else {
                var monto = undefined;
                monto = +$scope.laTran.monto;
                $scope.laTran.monto = +item.montorec + monto;
                var concepto = $scope.laTran.concepto;
                $scope.laTran.concepto = concepto.substring(0, 24) + item.reccli + ',' + concepto.substring(23) + ', ' + item.facturas;
            }
        };

        $scope.fillDataOnChangeBene = function (item, model) {
            $scope.setNombreBene(item);
            if (!$scope.laTran.concepto || $scope.laTran.concepto.trim() == '') {
                $scope.laTran.concepto = item.concepto;
            }
        };

        $scope.addTran = function (obj) {
            obj.idbanco = obj.objBanco.id;
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.tipotrans = obj.objTipotrans.abreviatura;
            obj.anticipo = obj.anticipo != null && obj.anticipo !== undefined ? obj.anticipo : 0;
            obj.esnegociable = obj.esnegociable != null && obj.esnegociable !== undefined ? obj.esnegociable : 0;
            obj.esnegociable = obj.tipotrans.toUpperCase() === 'C' ? obj.esnegociable : 0;
            obj.idbeneficiario = obj.objBeneficiario != null && obj.objBeneficiario !== undefined ? obj.objBeneficiario.id : 0;
            obj.origenbene = obj.objBeneficiario != null && obj.objBeneficiario !== undefined ? obj.objBeneficiario.dedonde : 0;
            obj.iddetpresup = obj.iddetpresup != null && obj.iddetpresup !== undefined ? obj.iddetpresup : 0;
            obj.iddetpagopresup = obj.iddetpagopresup != null && obj.iddetpagopresup !== undefined ? obj.iddetpagopresup : 0;
            obj.idproyecto = obj.idproyecto != null && obj.idproyecto !== undefined ? obj.idproyecto : 0;
            obj.iddocliquida = obj.iddocliquida != null && obj.iddocliquida !== undefined ? obj.iddocliquida : 0;
            obj.montooriginal = obj.montooriginal != null && obj.montooriginal !== undefined ? obj.montooriginal : 0.00;
            obj.retisr = obj.retisr != null && obj.retisr !== undefined ? obj.retisr : 0;
            obj.isr = obj.isr != null && obj.isr !== undefined ? obj.isr : 0.00;
            obj.montocalcisr = obj.montocalcisr != null && obj.montocalcisr !== undefined ? obj.montocalcisr : 0.00;
            obj.idrecibocli = obj.recibocli != null && obj.recibocli !== undefined ? obj.recibocli : 0;
            // console.log(obj); return;
            tranBancSrvc.editRow(obj, 'c').then(function (d) {
                $scope.getLstTran();
                $scope.getDataTran(parseInt(d.lastid));
            });
        };

        function processData(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].id = parseInt(data[i].id);
                data[i].idbanco = parseInt(data[i].idbanco);
                data[i].fecha = moment(data[i].fecha).toDate();
                data[i].numero = parseInt(data[i].numero);
                data[i].monto = parseFloat(parseFloat(data[i].monto).toFixed(2));
                data[i].operado = parseInt(data[i].operado);
                data[i].anticipo = parseInt(data[i].anticipo);
                data[i].esnegociable = parseInt(data[i].esnegociable);
                //data[i].idbeneficiario = parseInt(data[i].idbeneficiario);
                //data[i].origenbene = parseInt(data[i].origenbene);
                data[i].anulado = parseInt(data[i].anulado);
                data[i].fechaanula = moment(data[i].fechaanula).toDate();
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio));
                data[i].impreso = parseInt(data[i].impreso);
                data[i].fechaliquida = moment(data[i].fechaliquida).isValid() ? moment(data[i].fechaliquida).toDate() : null;
                data[i].iddetpagopresup = data[i].iddetpagopresup === 0 ? (+data[i].iddetpresup === 0 ? undefined : data[i].iddetpresup) : data[i].iddetpagopresup;
                data[i].iddocliquida = +data[i].iddocliquida === 0 ? undefined : data[i].iddocliquida;
                data[i].retisr = parseInt(data[i].retisr);
                data[i].montooriginal = parseFloat(parseFloat(data[i].montooriginal).toFixed(2));
                data[i].isr = parseFloat(parseFloat(data[i].isr).toFixed(2));
                data[i].montocalcisr = parseFloat(parseFloat(data[i].montocalcisr).toFixed(2));
            }
            return data;
        }

        function procDataDocs(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].idtipodoc = parseInt(data[i].idtipodoc);
                data[i].fechadoc = moment(data[i].fechadoc).toDate();
                data[i].documento = parseInt(data[i].documento);
                data[i].monto = parseFloat(data[i].monto);
                data[i].iddocto = parseInt(data[i].iddocto);
                //data[i].fechaliquida = moment(data[i].fechaliquida).isValid() ? moment(data[i].fechaliquida).toDate() : null;
            }
            return data;
        }

        function procDataDet(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].debe = parseFloat(data[i].debe);
                data[i].haber = parseFloat(data[i].haber);
            }
            return data;
        }

        $scope.checkTotales = function (idtran) {
            var totTran = parseFloat(parseFloat($scope.laTran.monto).toFixed(2));
            detContSrvc.getSumaPartida(1, idtran).then(function (d) {
                var sumdebe = parseFloat(parseFloat(d.sumdebe).toFixed(2)), sumhaber = parseFloat(parseFloat(d.sumhaber).toFixed(2))
                if (totTran === sumdebe && totTran === sumhaber && sumdebe === sumhaber) {
                    $scope.hayDescuadre = false;
                } else {
                    $scope.hayDescuadre = true;
                }
            });

        };

        $scope.getLiquidacion = function (idtran) {
            detContSrvc.lstDetalleCont($scope.origenLiq, idtran).then(function (liq) {
                $scope.liquidacion = procDataDet(liq);
                goTop();
            });
        };

        $scope.getDetCont = function (idtran) {
            detContSrvc.lstDetalleCont($scope.origen, idtran).then(function (detc) {
                $scope.losDetCont = procDataDet(detc);
                $scope.getLiquidacion(idtran);
                $scope.checkTotales(+idtran);
                goTop();
            });
        };

        function getByIdOrigen(input, id, origen) {
            for (var i = 0; i < input.length; i++) { if (+input[i].id == +id && +input[i].dedonde == +origen) { return input[i]; } }
            return null;
        }

        function formatoNumero(numero, decimales) { return $filter('number')(numero, decimales); }

        function getSumaDocumentosSoporte(idtran) {
            tranBancSrvc.getSumDocsSop(+idtran).then((suma) => $scope.sumaDocsSoporte = parseFloat(suma.totmonto));
        }

        getLstDocsSoporte = (idtran) => {
            tranBancSrvc.lstDocsSoporte(+idtran).then((det) => {
                $scope.losDocsSoporte = procDataDocs(det);
                $scope.compraspendientes = [];
                $scope.elDocSop = { fechadoc: moment().toDate(), fechaliquida: null };
                getSumaDocumentosSoporte(idtran);
            });
        }

        $scope.getDataTran = function (idtran) {
            $scope.editando = true;
            $scope.liquidacion = [];
            $scope.cargando = true;
            tranBancSrvc.getTransaccion(parseInt(idtran)).then(function (d) {
                $scope.laTran = processData(d)[0];
                $scope.laTran.objBanco = $filter('getById')($scope.losBancos, $scope.laTran.idbanco);

                var tmp = $scope.laTran, coma = ', ';
                $scope.strTran = (tmp.anticipo === 0 ? '' : 'Anticipo, ') + tmp.objBanco.nombre + ' (' + tmp.objBanco.nocuenta + ')' + coma;
                $scope.strTran += tmp.tipotrans + '-' + tmp.numero + coma;
                $scope.strTran += moment(tmp.fecha).format('DD/MM/YYYY') + coma + tmp.moneda + ' ' + formatoNumero(tmp.monto, 2) + coma + tmp.beneficiario;

                if ($scope.laTran.anticipo === 1 || +$scope.laTran.idbeneficiario > 0) {
                    //$scope.laTran.objBeneficiario = [getByIdOrigen($scope.beneficiarios, $scope.laTran.idbeneficiario, $scope.laTran.origenbene)];
                    var tmpObjBene = $filter('filter')($scope.beneficiarios, { id: $scope.laTran.idbeneficiario, dedonde: $scope.laTran.origenbene }, true);
                    $scope.laTran.objBeneficiario = tmpObjBene.length > 0 ? tmpObjBene[0] : undefined;
                }

                tipoMovTranBanSrvc.getByAbreviatura(d[0].tipotrans).then(function (res) {
                    $scope.laTran.objTipotrans = res[0];
                    tipoDocSopTBSrvc.lstTiposDocTB(parseInt(res[0].id)).then(function (d) { $scope.losTiposDocTB = d; });
                });

                getLstDocsSoporte(idtran);

                /*
                tranBancSrvc.lstDocsSoporte(parseInt(idtran)).then(function(det){
                    $scope.losDocsSoporte = procDataDocs(det);
                    $scope.compraspendientes = [];
                    $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
                    getSumaDocumentosSoporte(idtran);
                });
                */

                cuentacSrvc.getByTipo($scope.laEmpresa.id, 0).then(function (ctas) {
                    $scope.lasCuentasMov = ctas;
                    $scope.cargando = false;
                });

                $scope.getDetCont(parseInt(idtran));

                if ($scope.laTran.idrecibocli != null) {
                    // traer uno o mas recibos
                    $scope.laTran.recibocli = [];
                    reciboClientesSrvc.getLstRec($scope.laTran.id).then(function (d) {
                        $scope.recibo = d;

                        var lstRecTmp = $scope.laTran.idrecibocli.split(',');

                        for (var i = 0; i < lstRecTmp.length; i++) {
                            $scope.laTran.recibocli.push($filter('getById')($scope.recibo, +lstRecTmp[i]));
                        }
                    });
                }
            });

        };

        $scope.gcprint = function (obj) {
            // var gadget = new cloudprint.Gadget();
            //var url = "http://52.35.3.1/sayet/php/" + obj.objBanco.formato + ".php?c=" + obj.id;
            // var url = window.location.origin + "/sayet/php/" + obj.objBanco.formato + ".php?c=" + obj.id + "&uid=" + $scope.uid;
            //console.log(url);
            // gadget.setPrintDocument("url", "C" + obj.numero, url);
            // gadget.openPrintDialog();
        };

        $scope.modalPRINT = function (obj) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalPRINT.html',
                controller: 'ModalPrin',
                resolve: {
                    venta: function () { return $scope.venta; },
                    objbancos: function () { return obj; },
                    userid: function () { return $scope.uid }
                }
            });
        };

        $scope.printCheque = (idtran) => {
            tranBancSrvc.getInfoToPrint(idtran, $scope.uid).then((chqs) => {
                let objs = [];
                for (let i = 0; i < chqs.length; i++) {
                    objs.push({
                        tipo: 'C',
                        descripcionTipo: 'cheque',
                        datos: chqs[i]
                    });
                }
                // console.log(objs); return;
                socketIOSrvc.emit('sayet:print', JSON.stringify(objs));
            });
        };

        $scope.updTran = function (data, id) {
            data.idbanco = data.objBanco.id;
            data.fechastr = moment(data.fecha).format('YYYY-MM-DD');
            data.tipotrans = data.objTipotrans.abreviatura;
            data.anticipo = data.anticipo != null && data.anticipo !== undefined ? data.anticipo : 0;
            data.esnegociable = data.esnegociable != null && data.esnegociable !== undefined ? data.esnegociable : 0;
            data.esnegociable = data.tipotrans.toUpperCase() === 'C' ? data.esnegociable : 0;
            data.idbeneficiario = (parseInt(data.anticipo) === 0) ? 0 : (data.objBeneficiario != null && data.objBeneficiario !== undefined ? data.objBeneficiario.id : 0);
            data.origenbene = (parseInt(data.anticipo) === 0) ? 0 : (data.objBeneficiario != null && data.objBeneficiario !== undefined ? data.objBeneficiario.dedonde : 0);
            data.iddetpresup = data.iddetpresup != null && data.iddetpresup !== undefined ? data.iddetpresup : 0;
            data.iddetpagopresup = data.iddetpagopresup != null && data.iddetpagopresup !== undefined ? data.iddetpagopresup : 0;
            data.idproyecto = data.idproyecto != null && data.idproyecto !== undefined ? data.idproyecto : 0;
            data.iddocliquida = data.iddocliquida != null && data.iddocliquida !== undefined ? data.iddocliquida : 0;
            tranBancSrvc.editRow(data, 'u').then(function () {
                $scope.laTran = {
                    objBanco: data.objBanco,
                    objTipotrans: null,
                    concepto: ''
                };
                $scope.strTran = '';
                $scope.editando = false;
                $scope.getLstTran();
                $scope.getDataTran(+id);
            });
        };

        $scope.delTran = function (obj) {
            $confirm({
                text: '¿Seguro(a) de eliminar esta transacción? (Se liberarán los documentos de soporte, se eliminará el detalle contable de esta transacción y, en el caso de los cheques, se reseteará el correlativo a este número)',
                title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'
            }).then(function () {
                tranBancSrvc.editRow({ id: obj.id }, 'd').then(function () { $scope.getLstTran(); $scope.resetLaTran(); });
            });
        };

        $scope.anular = function (obj) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAnulacion.html',
                controller: 'ModalAnulacionCtrl',
                resolve: {
                    lstrazonanula: function () {
                        return $scope.razonesanula;
                    }
                }
            });

            modalInstance.result.then(function (datosAnula) {
                //console.log(datosAnula);
                obj.idrazonanulacion = datosAnula.idrazonanulacion;
                obj.fechaanulastr = datosAnula.fechaanulastr;
                //console.log(obj);
                tranBancSrvc.editRow(obj, 'anula').then(function () { $scope.getDataTran($scope.laTran.id); });
            }, function () { return 0; });
        };

        $scope.getCompras = function () {
            if (+$scope.laTran.id > 0) {
                tranBancSrvc.lstCompras(+$scope.laTran.id).then(function (d) { $scope.compras = d; });
            } else {
                $scope.compras = [];
            }
        };

        $scope.addDocSop = function (obj) {
            obj.idtranban = parseInt($scope.laTran.id);
            obj.fechadocstr = moment(obj.fechadoc).format('YYYY-MM-DD');
            obj.idtipodoc = obj.objTipoDocTB.id;
            obj.serie = obj.serie != null && obj.serie != undefined ? obj.serie : '';
            obj.iddocto = obj.objDocsPendientes[0] != null && obj.objDocsPendientes[0] != undefined ? obj.objDocsPendientes[0].id : 0;
            obj.montotran = $scope.laTran.monto;
            obj.idempresa = $scope.laEmpresa.id;
            obj.fechaliquidastr = moment(obj.fechaliquida).isValid() ? moment(obj.fechaliquida).format('YYYY-MM-DD') : '';

            tranBancSrvc.editRow(obj, 'cd').then(function () {
                getLstDocsSoporte($scope.laTran.id);
                /*
                tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                    $scope.losDocsSoporte = procDataDocs(det);
                    getSumaDocumentosSoporte($scope.laTran.id);
                });
                $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
                */
                $scope.getDetCont(obj.idtranban);
                $scope.getLiquidacion(obj.idtranban);
            });
        };

        $scope.zeroDebe = function (valor) { $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function (valor) { $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        $scope.addDetCont = function (obj) {
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.laTran.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta.id);
            detContSrvc.editRow(obj, 'c').then(function () {
                detContSrvc.lstDetalleCont($scope.origen, parseInt($scope.laTran.id)).then(function (detc) {
                    $scope.losDetCont = procDataDet(detc);
                    $scope.elDetCont = { debe: 0.0, haber: 0.0 };
                    $scope.searchcta = "";
                    $scope.checkTotales(+$scope.laTran.id);
                });
            });
        };

        $scope.loadDetaCont = function () {
            detContSrvc.lstDetalleCont($scope.origen, +$scope.laTran.id).then(function (detc) {
                $scope.losDetCont = procDataDet(detc);
                $scope.elDetCont = { debe: 0.0, haber: 0.0 };
                $scope.getLiquidacion(+$scope.laTran.id);
                $scope.checkTotales(+$scope.laTran.id);
            });
        };

        $scope.updDetCont = function (obj) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalUpdDetCont.html',
                controller: 'ModalUpdDetContCtrl',
                resolve: {
                    detalle: function () { return obj; },
                    idempresa: function () { return +$scope.laEmpresa.id; }
                }
            });

            modalInstance.result.then(function () {
                $scope.loadDetaCont();
            }, function () { $scope.loadDetaCont(); });
        };

        $scope.delDetCont = function (obj) {
            $confirm({ text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No' }).then(function () {
                detContSrvc.editRow({ id: obj.id }, 'd').then(function () { $scope.getDetCont(obj.idorigen); $scope.checkTotales(+obj.idorigen); });
            });
        };

        $scope.printVersion = function () {
            //PrintElem('#toPrint', 'Transacción bancaria');
            var test = false;
            tranBancSrvc.imprimir(+$scope.laTran.id).then(function (d) {
                jsReportSrvc.getPDFReport(test ? 'r1V2bJYkW' : 'rJStGGt1-', d).then(function (pdf) {
                    $window.open(pdf);
                });
            });
        };

        $scope.updateDetRecCli = function (obj) {
            $confirm({ text: '¿Seguro(a) de actualizar monto aplicado de este documento?', title: 'Modificación', ok: 'Sí', cancel: 'No' }).then(function () {

                tranBancSrvc.editRow({ idtipodoc: obj.idtipodoc, documento: obj.documento, fechadocstr: obj.fechadoc, serie: obj.serie, iddocto: obj.iddocto, id: obj.id, monto: obj.monto }, 'ud').then(function () {
                    getLstDocsSoporte($scope.laTran.id);
                    /*
                    tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                        $scope.losDocsSoporte = procDataDocs(det);
                    });
                    */
                });
                $scope.reset(obj);
            });
        };

        $scope.delDetRecCli = function (obj) {
            $confirm({ text: '¿Seguro(a) de eliminar este documento? (Esto dejará como pendiente el documento)', title: 'Eliminar documento rebajado', ok: 'Sí', cancel: 'No' }).then(function () {
                tranBancSrvc.editRow({ id: obj.id, iddocto: obj.iddocto }, 'dd').then(function () {
                    getLstDocsSoporte($scope.laTran.id);
                    /*
                    tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                        $scope.losDocsSoporte = procDataDocs(det);
                    });
                    */
                });
            });
        };
        $scope.editDetRecCli = function (obj) {
            $scope.selected = angular.copy(obj);
        };

        $scope.getTemplate = function (obj) {
            if (obj.id === $scope.selected.id) {
                return 'edit';
            }
            else return 'display';
        };

        $scope.reset = function (obj) {
            $scope.selected = {};
            $scope.periodoCerrado = false;
            getLstDocsSoporte($scope.laTran.id);
            /*
            tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                $scope.losDocsSoporte = procDataDocs(det);
            });
            */
        };

        $scope.updTranAnul = (obj) => {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'editarAnulacion.html',
                controller: 'ModalUpdAnulaCtrl',
                resolve: {
                    transaccion: () => obj
                }
            });

            modalInstance.result.then(() => {
                $scope.getLstTran();
                $scope.getDataTran(+obj.id);
            }, () => { });
        };

        $scope.printSelloFactura = (idtranban) => jsReportSrvc.getPDFReport('S1Uc-8wYv', { idtranban: idtranban }).then((pdf) => $window.open(pdf));

        $scope.printSelloNotaCredito = (idtranban) => jsReportSrvc.getPDFReport('S1H3T8uFw', { idtranban: idtranban }).then((pdf) => $window.open(pdf));

        $scope.calcIsr = function (obj) {
            //console.log(obj); return;
            tranBancSrvc.calcIsr(obj).then(function (d) {
                $scope.laTran.isr = d.isr,
                    $scope.laTran.monto = d.monto
            });
        };
    }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalUpdAnulaCtrl', ['$scope', '$uibModalInstance', 'transaccion', 'tranBancSrvc', function ($scope, $uibModalInstance, transaccion, tranBancSrvc) {
        $scope.transaccion = transaccion;
        $scope.params = { id: $scope.transaccion.id, fechaanula: moment().toDate(), fechaanulastr: undefined };

        $scope.ok = function () {
            $scope.params.fechaanulastr = moment($scope.params.fechaanula).isValid() ? moment($scope.params.fechaanula).format('YYYY-MM-DD') : '';
            tranBancSrvc.editRow($scope.params, 'uda').then(() => $uibModalInstance.close());
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalAnulacionCtrl', ['$scope', '$uibModalInstance', 'lstrazonanula', function ($scope, $uibModalInstance, lstrazonanula) {
        $scope.razones = lstrazonanula;
        $scope.razon = [];
        $scope.anuladata = { idrazonanulacion: 0, fechaanula: moment().toDate() };

        $scope.ok = function () {
            $scope.anuladata.idrazonanulacion = $scope.razon.id;
            $scope.anuladata.fechaanulastr = moment($scope.anuladata.fechaanula).format('YYYY-MM-DD');
            //console.log($scope.anuladata);
            $uibModalInstance.close($scope.anuladata);
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function ($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm) {
        $scope.detcont = detalle;
        $scope.cuentas = [];

        cuentacSrvc.getByTipo(idempresa, 0).then(function (d) { $scope.cuentas = d; });

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

        $scope.zeroDebe = function (valor) { $scope.detcont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.debe; };
        $scope.zeroHaber = function (valor) { $scope.detcont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.haber; };

        $scope.actualizar = function (obj) {
            $confirm({ text: '¿Seguro(a) de guardar los cambios?', title: 'Modificar detalle contable', ok: 'Sí', cancel: 'No' }).then(function () {
                detContSrvc.editRow(obj, 'u').then(function () { $scope.ok(); });
            });
        };

    }]);

    //Controlador de formulario de impresion cheques continuos
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalPrin', ['$scope', '$uibModalInstance', 'venta', 'userid', 'objbancos', 'tranBancSrvc', 'socketIOSrvc', function ($scope, $uibModalInstance, venta, userid, objbancos, tranBancSrvc, socketIOSrvc) {
        $scope.venta = venta;
        $scope.losBancos = objbancos;
        $scope.correlativos = [];

        $scope.ok = function () {
            $scope.venta.ndel = $scope.venta.ndel != null && $scope.venta.ndel != undefined ? $scope.venta.ndel : '';
            $scope.venta.nal = $scope.venta.nal != null && $scope.venta.nal != undefined ? $scope.venta.nal : '';
            $scope.venta.idbanco = $scope.losBancos.id.id != null && $scope.losBancos.id.id != undefined ? $scope.losBancos.id.id : '';

            tranBancSrvc.getBatchInfoToPrint($scope.venta.idbanco, $scope.venta.ndel, $scope.venta.nal, userid).then((chqs) => {
                let objs = [];
                for (let i = 0; i < chqs.length; i++) {
                    objs.push({
                        tipo: 'C',
                        descripcionTipo: 'cheque',
                        datos: chqs[i]
                    });
                }
                // console.log(objs); return;
                socketIOSrvc.emit('sayet:print', JSON.stringify(objs));
            });
        };
        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };



    }]);

}());
