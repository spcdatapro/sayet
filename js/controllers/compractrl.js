(function () {

    var compractrl = angular.module('cpm.compractrl', ['cpm.comprasrvc']);

    compractrl.controller('compraCtrl', [
        '$scope', '$filter', 'compraSrvc', 'authSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'proveedorSrvc', 'tipoCompraSrvc', 'toaster', 'cuentacSrvc', 'detContSrvc', '$uibModal', '$confirm', 'monedaSrvc', 'tipoFacturaSrvc',
        'tipoCombustibleSrvc', 'presupuestoSrvc', 'proyectoSrvc', 'jsReportSrvc', '$window', 'periodoContableSrvc', 'tipoCambioSrvc', 'servicioBasicoSrvc',
        ($scope, $filter, compraSrvc, authSrvc, empresaSrvc, DTOptionsBuilder, proveedorSrvc, tipoCompraSrvc, toaster, cuentacSrvc, detContSrvc, $uibModal, $confirm, monedaSrvc, tipoFacturaSrvc,
            tipoCombustibleSrvc, presupuestoSrvc, proyectoSrvc, jsReportSrvc, $window, periodoContableSrvc, tipoCambioSrvc, servicioBasicoSrvc
        ) => {

            $scope.lasEmpresas = [];
            $scope.lasCompras = [];
            var hoy = new Date();
            $scope.laCompra = { galones: 0.00, idp: 0.00, ordentrabajo: undefined };
            $scope.editando = false;
            $scope.losProvs = [];
            $scope.losTiposCompra = [];
            $scope.losDetCont = [];
            $scope.elDetCont = { debe: 0.0, haber: 0.0 };
            $scope.lasCtasMov = [];
            $scope.origen = 2;
            $scope.ctasGastoProv = [];
            $scope.yaPagada = false;
            $scope.tranpago = [];
            $scope.monedas = [];
            $scope.dectc = 5;
            $scope.lsttiposfact = [];
            $scope.combustibles = [];
            $scope.facturastr = '';
            $scope.fltrcomp = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), idot: undefined };
            $scope.proyectos = [];
            $scope.unidades = [];
            $scope.params = { idcompra: 0 };
            $scope.lstproyectoscompra = [];
            $scope.proyectocompra = {};
            $scope.itemsLimit = 10;
            $scope.periodoCerrado = false;
            $scope.presupuesto = {};
            $scope.ot = {};
            $scope.montoMax = 999999999;
            $scope.losCheques = [];
            $scope.tipocambiobgt = {};
            $scope.lasFacturas = [];
            $scope.docsLiquida = [];
            $scope.liquida = false;
            $scope.servicios = [];

            $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('fnRowCallback', rowCallback);

            empresaSrvc.lstEmpresas().then(function (d) { $scope.lasEmpresas = d; });
            tipoCambioSrvc.getLastTC().then(function (d) { $scope.tipocambiogt = +d.lasttc; });

            tipoFacturaSrvc.lstTiposFyN().then(function (d) {
                for (var i = 0; i < d.length; i++) { d[i].id = parseInt(d[i].id); d[i].paracompra = parseInt(d[i].paracompra); }
                $scope.lsttiposfact = d;
            });

            tipoCombustibleSrvc.lstTiposCombustible().then(function (d) {
                for (var i = 0; i < d.length; i++) {
                    d[i].id = parseInt(d[i].id);
                    d[i].impuesto = parseFloat(parseFloat(d[i].impuesto).toFixed(2));
                }
                $scope.combustibles = d;
            });

            authSrvc.getSession().then(function (usrLogged) {
                if (parseInt(usrLogged.workingon) > 0) {
                    empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function (d) {
                        $scope.laCompra.objEmpresa = d[0];
                        $scope.dectc = parseInt(d[0].dectc);
                        monedaSrvc.lstMonedas().then(function (l) {
                            $scope.monedas = l;
                            proyectoSrvc.lstProyectosPorEmpresa(+$scope.laCompra.objEmpresa.id).then(function (d) {
                                $scope.proyectos = d;
                                $scope.resetCompra();
                            });
                        });
                    });
                }
            });

            proveedorSrvc.lstProveedores().then(function (d) { $scope.losProvs = d; });

            tipoCompraSrvc.lstTiposCompra().then(function (d) {
                for (var i = 0; i < d.length; i++) { d[i].id = parseInt(d[i].id); }
                $scope.losTiposCompra = d;
            });

            $scope.loadPresupuestosProveedor = (idproveedor) => compraSrvc.getOtsProveedor(idproveedor, (!!$scope.laCompra.objEmpresa ? $scope.laCompra.objEmpresa.id : $scope.laCompra.idempresa)).then((d) => $scope.ots = d);

            $scope.loadFacturas = (idproveedor) => compraSrvc.getFacturas(idproveedor, (!!$scope.laCompra.objEmpresa ? $scope.laCompra.objEmpresa.id : $scope.laCompra.idempresa)).then((d) => $scope.lasFacturas = d);

            $scope.loadDataProvs = () => $scope.itemsLimit = $scope.itemsLimit + 10;

            $scope.fetch = function ($select) { };

            $scope.loadUnidadesProyecto = (idproyecto) => proyectoSrvc.lstUnidadesProyecto(+idproyecto).then((d) => $scope.unidades = d);

            $scope.loadServicios = (idunidad) => servicioBasicoSrvc.getContadores(+idunidad).then((d) => $scope.servicios = d);

            $scope.proyectoSelected = (item) => $scope.loadUnidadesProyecto(item.id);

            $scope.unidadSelected = (item) =>$scope.loadServicios(item.id);

            $scope.fillDataCompraOt = (idot) => {
                const idx = $scope.ots.findIndex(i => +i.id === +idot);
                if (idx > -1) {
                    const otSelected = $scope.ots[idx];
                    $scope.laCompra.objMoneda = $scope.monedas.find(m => +m.id === +otSelected.idmoneda);
                    $scope.laCompra.idproyecto = otSelected.idproyecto;
                    $scope.laCompra.conceptomayor = otSelected.notas;
                    compraSrvc.getMontoOt(idot).then(d => {
                        $scope.montoMax = d.monto;
                    });
                    compraSrvc.getCheques(idot).then(d => { $scope.losCheques = d; });
                } else {
                    $scope.montoMax = 999999999;
                }
            }

            $scope.resetCompra = function () {
                $scope.laCompra = {
                    fechaingreso: new Date(), mesiva: hoy.getMonth() + 1, fechafactura: new Date(), creditofiscal: 0, extraordinario: 0, noafecto: 0.0,
                    objEmpresa: $scope.laCompra.objEmpresa, objMoneda: {}, tipocambio: 1, isr: 0.00, galones: 0.00, idp: 0.00, objTipoCombustible: {},
                    totfact: 0.00, subtotal: 0.00, iva: 0.00, ordentrabajo: undefined, idproyecto: undefined, idunidad: undefined, nombrerecibo: undefined,
                    idcheque: undefined, alcontado: 0, iddocliquida: undefined, idservicio: undefined, lecturaini: undefined, 
                    lecturafin: undefined, preciouni: undefined, ffin: new Date(), fini: moment().startOf('month').toDate()
                };
                $scope.search = "";
                $scope.facturastr = '';
                $scope.losDetCont = [];
                $scope.tranpago = [];
                $scope.docsLiquida = [];
                $scope.yaPagada = false;
                $scope.editando = false;
                $scope.liquida = false;
                monedaSrvc.getMoneda(parseInt($scope.laCompra.objEmpresa.idmoneda)).then(function (m) {
                    $scope.laCompra.objMoneda = m[0];
                    $scope.laCompra.tipocambio = parseFloat(m[0].tipocambio).toFixed($scope.dectc);
                });
                $scope.periodoCerrado = false;
                $scope.unidades = [];
                $scope.ots = [];
                goTop();
            };

            $scope.chkFecha = function (qFecha, cual) {
                if (qFecha != null && qFecha != undefined) {
                    switch (cual) {
                        case 1:
                            if ($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined) {
                                $scope.laCompra.fechapago = moment(qFecha).add(parseInt($scope.laCompra.objProveedor.diascred), 'days').toDate();
                            }
                            break;
                    }
                }
            };

            $scope.setMesIva = function (fing) {
                if (fing != null && fing != undefined) {
                    $scope.laCompra.mesiva = (moment(fing).month() + 1);
                } else {
                    $scope.laCompra.mesiva = undefined;
                }
            };

            $scope.$watch('laCompra.fechaingreso', function (newValue, oldValue) {
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
                                //$scope.laCompra.fechaingreso = null;
                                toaster.pop({
                                    type: 'error', title: 'Fecha de ingreso es inválida.',
                                    body: 'No está dentro de ningún período contable abierto.', timeout: 7000
                                });
                            } else {
                                $scope.periodoCerrado = false;
                            }
                        });
                    }
                }
            };

            $scope.chkExisteCompra = function () {
                //console.log($scope.laCompra); return;
                var params = { idproveedor: 0, nit: '', serie: '', documento: 0, ordentrabajo: 0 };
                if ($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined) {
                    params.idproveedor = +$scope.laCompra.objProveedor.id;
                    params.nit = $scope.laCompra.objProveedor.nit != null && $scope.laCompra.objProveedor.nit != undefined ? $scope.laCompra.objProveedor.nit.trim() : '';
                }

                if ($scope.laCompra.serie != null && $scope.laCompra.serie != undefined) { params.serie = $scope.laCompra.serie.trim(); }

                if ($scope.laCompra.documento != null && $scope.laCompra.documento != undefined) { params.documento = +$scope.laCompra.documento; }

                if (params.documento > 0) {
                    compraSrvc.existeCompra(params).then(function (d) {
                        if (+d.existe == 1) {
                            var mensaje = 'La factura ' + d.serie + '-' + d.documento + ' del proveedor ' + d.proveedor + ' (' + d.nit + ') ';
                            mensaje += 'ya existe en la empresa ' + d.empresa + ' (' + d.abreviaempresa + '). Favor revisar.';
                            toaster.pop({
                                type: 'error', title: 'Esta factura ya existe',
                                body: mensaje, timeout: 9000
                            });
                        }
                    });
                }
            };

            function esCombustible() {
                if ($scope.laCompra.objTipoCompra != null && $scope.laCompra.objTipoCompra != undefined) {
                    if ($scope.laCompra.objTipoCompra.id != null && $scope.laCompra.objTipoCompra.id != undefined) {
                        if (parseInt($scope.laCompra.objTipoCompra.id) == 3) {
                            return true;
                        }
                    }

                }
                return false;
            }


            calcIDP = (genidp) => {
                //if (genidp && $scope.laCompra.objTipoCombustible != null && $scope.laCompra.objTipoCombustible != undefined) {
                if (genidp && !!$scope.laCompra.objTipoCombustible) {
                    //const galones = $scope.laCompra.galones != null && $scope.laCompra.galones != undefined ? parseFloat($scope.laCompra.galones) : 0.00;
                    const galones = !!$scope.laCompra.galones ? parseFloat($scope.laCompra.galones) : 0.00;
                    //const impuesto = $scope.laCompra.objTipoCombustible.impuesto != null && $scope.laCompra.objTipoCombustible.impuesto != undefined ? parseFloat($scope.laCompra.objTipoCombustible.impuesto) : 0.00;
                    const impuesto = !!$scope.laCompra.objTipoCombustible.impuesto ? parseFloat($scope.laCompra.objTipoCombustible.impuesto) : 0.00;
                    return (galones * impuesto).toFixed(2);
                }
                return 0.00;
            }

            $scope.calcular = function () {
                let geniva = true;
                const genidp = esCombustible();
                //var totFact = $scope.laCompra.totfact != null && $scope.laCompra.totfact != undefined ? parseFloat($scope.laCompra.totfact) : 0;
                const totFact = !!$scope.laCompra.totfact ? parseFloat($scope.laCompra.totfact) : 0;
                //var noAfecto = $scope.laCompra.noafecto != null && $scope.laCompra.noafecto != undefined ? parseFloat($scope.laCompra.noafecto) : 0;
                const noAfecto = !!$scope.laCompra.noafecto ? parseFloat($scope.laCompra.noafecto) : 0;
                let exento = 0.00, subtotal = 0.00;

                //if ($scope.laCompra.objTipoFactura != null && $scope.laCompra.objTipoFactura != undefined) { geniva = parseInt($scope.laCompra.objTipoFactura.generaiva) === 1; }
                if (!!$scope.laCompra.objTipoFactura) { geniva = parseInt($scope.laCompra.objTipoFactura.generaiva) === 1; }

                $scope.laCompra.idp = calcIDP(genidp);

                exento = parseFloat($scope.laCompra.idp) + noAfecto;
                subtotal = totFact - exento;

                if ($scope.laCompra.objProveedor.pequeniocont == 1) {
                    $scope.laCompra.subtotal = totFact;
                    $scope.laCompra.iva = 0.00;
                } else {
                    if (noAfecto <= totFact) {
                        $scope.laCompra.subtotal = geniva ? parseFloat(subtotal / 1.12).toFixed(2) : totFact;
                        $scope.laCompra.iva = geniva ? parseFloat($scope.laCompra.subtotal * 0.12).toFixed(2) : 0.00;
                    } else {
                        $scope.laCompra.noafecto = 0;
                        toaster.pop({
                            type: 'error', title: 'Error en el monto de No afecto.',
                            body: 'El monto de No afecto no puede ser mayor al total de la factura.', timeout: 7000
                        });
                    }
                }
            };

            $scope.esDePresupuesto = async () => {
                if (+$scope.idot > 0 && !$scope.ot.id) {
                    // console.log('ID OT DESDE COMPRA = ', +$scope.idot);
                    $scope.fltrcomp.idot = +$scope.idot;
                    await presupuestoSrvc.getOt($scope.idot).then(d => {
                        $scope.ot = d[0];
                        $scope.getCompra(0, $scope.fltrcomp.idot);
                    });
                    await presupuestoSrvc.getPresupuesto($scope.ot.idpresupuesto).then(d => { $scope.presupuesto = d[0]; });
                }
            };

            $scope.$watch('laCompra.objEmpresa', function (newValue, oldValue) {
                if (newValue != null && newValue != undefined) {
                    $scope.esDePresupuesto();
                    $scope.getLstCompras();
                }
            });

            $scope.getConcepto = (qProv) => {
                $scope.chkExisteCompra();
                $scope.loadPresupuestosProveedor(qProv.id);
                $scope.loadFacturas(qProv.id);
                if (!$scope.laCompra.id > 0) {
                    if (!!qProv) {
                        $scope.laCompra.conceptomayor = qProv.concepto;
                        $scope.laCompra.fechapago = moment($scope.laCompra.fechaingreso).add(parseInt(qProv.diascred), 'days').toDate();
                        $scope.laCompra.objMoneda = $filter('getById')($scope.monedas, parseInt(qProv.idmoneda));
                        $scope.laCompra.tipocambio = parseFloat(qProv.tipocambioprov).toFixed($scope.dectc);
                    }
                }
            };

            $scope.setTipoCambio = function (qmoneda) {
                if ($scope.laCompra.id > 0 || ($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined)) {
                    if (parseInt(qmoneda.id) === parseInt($scope.laCompra.objProveedor.idmoneda)) {
                        $scope.laCompra.tipocambio = parseFloat($scope.laCompra.objProveedor.tipocambioprov);
                    } else {
                        $scope.laCompra.tipocambio = parseFloat(qmoneda.tipocambio).toFixed($scope.dectc);
                    }
                } else { $scope.laCompra.tipocambio = parseFloat(qmoneda.tipocambio).toFixed($scope.dectc); }
            };

            dateToStr = (fecha) => !!fecha ? (fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate()) : '';

            function procDataCompras(data) {
                for (var i = 0; i < data.length; i++) {
                    data[i].documento = parseInt(data[i].documento);
                    data[i].mesiva = parseInt(data[i].mesiva);
                    data[i].totfact = parseFloat(parseFloat(data[i].totfact).toFixed(2));
                    data[i].noafecto = parseFloat(parseFloat(data[i].noafecto).toFixed(2));
                    data[i].subtotal = parseFloat(parseFloat(data[i].subtotal).toFixed(2));
                    data[i].iva = parseFloat(parseFloat(data[i].iva).toFixed(2));
                    data[i].isr = parseFloat(parseFloat(data[i].isr).toFixed(2));
                    data[i].fechaingreso = moment(data[i].fechaingreso).toDate();
                    data[i].fechafactura = moment(data[i].fechafactura).toDate();
                    data[i].fechapago = moment(data[i].fechapago).toDate();
                    data[i].creditofiscal = parseInt(data[i].creditofiscal);
                    data[i].extraordinario = parseInt(data[i].extraordinario);
                    data[i].idproveedor = parseInt(data[i].idproveedor);
                    data[i].idtipocompra = parseInt(data[i].idtipocompra);
                    data[i].cantpagos = parseInt(data[i].cantpagos);
                    data[i].idmoneda = parseInt(data[i].idmoneda);
                    data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed($scope.dectc));
                    data[i].idtipofactura = parseInt(data[i].idtipofactura);
                    data[i].idtipocombustible = parseInt(data[i].idtipocombustible);
                    data[i].galones = parseFloat(parseFloat(data[i].galones).toFixed(2));
                    data[i].galones = parseFloat(parseFloat(data[i].galones).toFixed(2));
                    data[i].idp = parseFloat(parseFloat(data[i].idp).toFixed(2));
                    data[i].alcontado = +data[i].alcontado;
                    data[i].fecpagoformisr = moment(data[i].fecpagoformisr).isValid() ? moment(data[i].fecpagoformisr).toDate() : null;
                    data[i].lecturaini = +data[i].lecturaini;
                    data[i].lecturafin = +data[i].lecturafin;
                    data[i].fini = moment(data[i].fechaini).toDate();
                    data[i].ffin = moment(data[i].fechafin).toDate();
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

            $scope.getLstCompras = function () {
                $scope.fltrcomp.fdelstr = moment($scope.fltrcomp.fdel).format('YYYY-MM-DD');
                $scope.fltrcomp.falstr = moment($scope.fltrcomp.fal).format('YYYY-MM-DD');
                $scope.fltrcomp.idempresa = +$scope.laCompra.objEmpresa.id;
                compraSrvc.lstComprasFltr($scope.fltrcomp).then(function (d) {
                    $scope.lasCompras = procDataCompras(d);
                });
            };

            $scope.getDetCont = (idcomp) => detContSrvc.lstDetalleCont($scope.origen, parseInt(idcomp)).then(function (detc) { $scope.losDetCont = procDataDet(detc); });

            $scope.modalISR = function () {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalISR.html',
                    controller: 'ModalISR',
                    resolve: {
                        compra: function () {
                            return $scope.laCompra;
                        }
                    }
                });

                modalInstance.result.then(function (idcompra) {
                    $scope.getCompra(parseInt(idcompra));
                }, function () { return 0; });
            };

            formatoNumero = (numero, decimales) => $filter('number')(numero, decimales);

            $scope.getCompra = function (idcomp, idot) {
                $scope.losDetCont = [];
                $scope.ots = [];
                $scope.elDetCont = { debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined };

                compraSrvc.getCompra(idcomp, idot).then((d) => {
                    if (d.length > 0) {
                        $scope.laCompra = procDataCompras(d)[0];
                        $scope.laCompra.objProveedor = $filter('getById')($scope.losProvs, $scope.laCompra.idproveedor);
                        $scope.loadFacturas($scope.laCompra.idproveedor);
                        $scope.laCompra.objMoneda = $filter('getById')($scope.monedas, $scope.laCompra.idmoneda);
                        $scope.laCompra.objTipoFactura = $filter('getById')($scope.lsttiposfact, $scope.laCompra.idtipofactura);
                        $scope.laCompra.objTipoCombustible = $filter('getById')($scope.combustibles, $scope.laCompra.idtipocombustible);
                        tipoCompraSrvc.getTipoCompra($scope.laCompra.idtipocompra).then(function (tc) { $scope.laCompra.objTipoCompra = tc[0]; });
                        $scope.editando = true;
                        cuentacSrvc.getByTipo($scope.laCompra.idempresa, 0).then(function (d) { $scope.lasCtasMov = d; });
                        $scope.loadUnidadesProyecto($scope.laCompra.idproyecto);
                        $scope.loadServicios($scope.laCompra.idunidad);
                        $scope.getDetCont($scope.laCompra.id);
                        $scope.loadProyectosCompra($scope.laCompra.id);
                        $scope.resetProyectoCompra();
                        $scope.loadPresupuestosProveedor($scope.laCompra.idproveedor);
                        empresaSrvc.getEmpresa(parseInt($scope.laCompra.idempresa)).then(function (d) { $scope.laCompra.objEmpresa = d[0]; });
                        compraSrvc.getTransPago($scope.laCompra.id).then(function (d) {
                            for (var i = 0; i < d.length; i++) {
                                d[i].idtranban = parseInt(d[i].idtranban);
                                d[i].numero = parseInt(d[i].numero);
                                d[i].monto = parseFloat(d[i].monto);
                            }
                            $scope.tranpago = d;
                            $scope.yaPagada = $scope.tranpago.length > 0;
                        });

                        compraSrvc.getDocLiquida($scope.laCompra.id).then(function(d) {
                            for (var i = 0; i < d.length; i++) {
                                d[i].factura = d[i].factura;
                                d[i].monto = parseFloat(d[i].monto);
                                d[i].moneda = d[i].moneda;
                            }
                            $scope.docsLiquida = d;
                            $scope.liquida = $scope.docsLiquida.length > 0;
                        });

                        if ($scope.laCompra.isr > 0) {
                            if ($scope.laCompra.noformisr == '' || $scope.laCompra.noformisr == undefined || $scope.laCompra.noformisr == null) {
                                $scope.modalISR();
                            }
                        }
                        var tmp = $scope.laCompra, coma = ', ';

                        $scope.facturastr = tmp.nomproveedor + coma + tmp.siglas + '-' + tmp.serie + '-' + tmp.documento + coma + moment(tmp.fechafactura).format('DD/MM/YYYY') + coma + tmp.desctipocompra + coma + 'Total: ' + tmp.moneda + ' ';
                        $scope.facturastr += formatoNumero(tmp.totfact, 2) + coma + 'No afecto: ' + tmp.moneda + ' ' + formatoNumero(tmp.noafecto, 2) + coma + ' Subtotal: ' + tmp.moneda + ' ' + formatoNumero(tmp.subtotal, 2) + coma;
                        $scope.facturastr += 'I.V.A.: ' + tmp.moneda + ' ' + formatoNumero(tmp.iva, 2) + coma + 'I.S.R.: ' + tmp.moneda + ' ' + formatoNumero(tmp.isr, 2) + coma + 'I.D.P.: ' + tmp.moneda + ' ' + formatoNumero(tmp.idp, 2);
                    } else {
                        //console.log('PROVEEDORES = ', $scope.losProvs);
                        $scope.laCompra.objProveedor = $filter('getById')($scope.losProvs, $scope.ot.idproveedor);
                        $scope.laCompra.idproyecto = $scope.presupuesto.idproyecto;
                        $scope.getConcepto($scope.laCompra.objProveedor);
                        $scope.laCompra.totfact = $scope.ot.monto;
                        $scope.calcular();
                    }
                    goTop();
                });

            };

            function execCreate(obj) {
                compraSrvc.editRow(obj, 'c').then(function (d) {
                    if (+d.lastid > 0) {
                        $scope.getLstCompras();
                        $scope.getCompra(parseInt(d.lastid));
                    } else {
                        toaster.pop({
                            type: 'error', title: 'Error en la creación de la factura.',
                            body: 'La factura de este proveedor no pudo ser creada. Favor verifique que los datos estén bien ingresados y que la factura de este proveedor no exista.', timeout: 9000
                        });
                    }
                });
            }

            function execUpdate(obj) {
                //console.log(obj);
                compraSrvc.editRow(obj, 'u').then(function (d) {
                    $scope.getLstCompras();
                    $scope.getCompra(parseInt(d.lastid));
                });
            }

            $scope.openSelectCtaGastoProv = function (obj, op) {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalSelectCtaGastoProv.html',
                    controller: 'ModalCtasGastoProvCtrl',
                    resolve: {
                        lstctasgasto: function () {
                            return $scope.ctasGastoProv;
                        }
                    }
                });

                modalInstance.result.then(function (selectedItem) {
                    obj.ctagastoprov = selectedItem.idcuentac;
                    switch (op) {
                        case 'c': execCreate(obj); break;
                        case 'u': execUpdate(obj); break;
                    }
                }, function () { return 0; });
            };

            setObjCompra = (obj) => {
                obj.idempresa = parseInt(obj.objEmpresa.id);
                obj.idproveedor = parseInt(obj.objProveedor.id);
                obj.conceptoprov = obj.objProveedor.concepto;
                obj.idtipocompra = parseInt(obj.objTipoCompra.id);
                obj.creditofiscal = obj.creditofiscal != null && obj.creditofiscal != undefined ? obj.creditofiscal : 0;
                obj.extraordinario = obj.extraordinario != null && obj.extraordinario != undefined ? obj.extraordinario : 0;
                obj.ordentrabajo = +$scope.idpresupuesto > 0 ? +$scope.idpresupuesto : (!!obj.ordentrabajo ? obj.ordentrabajo : 0);
                obj.fechaingresostr = dateToStr(obj.fechaingreso);
                obj.fechafacturastr = dateToStr(obj.fechafactura);
                obj.fechapagostr = dateToStr(obj.fechapago);
                obj.idmoneda = parseInt(obj.objMoneda.id);
                obj.idtipofactura = parseInt(obj.objTipoFactura.id);
                obj.idtipocombustible = obj.objTipoCombustible != null && obj.objTipoCombustible != undefined ? (obj.objTipoCombustible.id != null && obj.objTipoCombustible.id != undefined ? obj.objTipoCombustible.id : 0) : 0;
                obj.idunidad = obj.idunidad != null && obj.idunidad !== undefined ? +obj.idunidad : 0;
                // obj.ordencompra = obj.ordencompra !== null && obj.ordencompra !== undefined ? obj.ordencompra : 0;
                if (obj.nombrerecibo == null || obj.nombrerecibo == undefined) {
                    delete obj.nombrerecibo;
                }
                obj.alcontado = obj.alcontado != null && obj.alcontado !== undefined ? +obj.alcontado : 0;
                obj.iddocliquida = obj.iddocliquida != null && obj.iddocliquida !== undefined ? obj.iddocliquida : null;
                //obj.idtipocombustible = 0;
                //obj.idproyecto = 0;
                obj.idservicio = obj.idservicio !== null && obj.idservicio !== undefined ? +obj.idservicio : null;
                obj.lecturaini = obj.lecturaini !== null && obj.lecturaini !== undefined ? +obj.lecturaini : null;
                obj.lecturafin = obj.lecturafin !== null && obj.lecturafin !== undefined ? +obj.lecturafin : null;
                obj.preciouni = obj.preciouni !== null && obj.preciouni !== undefined ? obj.preciouni : null;
                obj.ffin = obj.ffin !== null && obj.ffin !== undefined ? dateToStr(obj.ffin) : null;
                obj.fini = obj.fini !== null && obj.fini !== undefined ? dateToStr(obj.fini) : null;
                return obj;
            }

            $scope.addCompra = (obj) => {
                console.log(obj);
                obj = setObjCompra(obj);
                proveedorSrvc.getLstCuentasCont(obj.idproveedor, obj.idempresa).then((lstCtas) => {
                    $scope.ctasGastoProv = lstCtas;
                    switch (true) {
                        case $scope.ctasGastoProv.length == 0:
                            proveedorSrvc.lstCuentacProv(obj.idproveedor, obj.idempresa).then((lstCtas) => {
                                $scope.ctasGastoProv = lstCtas;
                                if ($scope.ctasGastoProv.length == 1) {
                                    obj.ctagastoprov = parseInt($scope.ctasGastoProv[0].idcuentac);
                                    execCreate(obj);
                                }
                                else {
                                    toaster.pop({
                                        type: 'error', title: 'Error en la creación de la factura.',
                                        body: 'La factura de este proveedor no pudo ser creada. Favor verifique que el proveedor tenga una cuenta contable.', timeout: 9000
                                    });
                                };
                            });
                            break;
                        case $scope.ctasGastoProv.length == 1:
                            obj.ctagastoprov = parseInt($scope.ctasGastoProv[0].idcuentac);
                            execCreate(obj);
                            break;
                        case $scope.ctasGastoProv.length > 1:
                            $scope.openSelectCtaGastoProv(obj, 'c');
                            break;
                    }
                });
            };

            $scope.updCompra = (obj) => {
                $confirm({
                    text: 'Este proceso eliminará el detalle contable que ya se haya ingresado y se creará uno nuevo. ¿Seguro(a) de continuar?',
                    title: 'Actualización de factura de compra', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    obj = setObjCompra(obj);
                    proveedorSrvc.getLstCuentasCont(obj.idproveedor, obj.idempresa).then((lstCtas) => {
                        $scope.ctasGastoProv = lstCtas;
                        switch (true) {
                            case $scope.ctasGastoProv.length == 0:
                                obj.ctagastoprov = 0;
                                //console.log(obj);
                                execUpdate(obj);
                                break;
                            case $scope.ctasGastoProv.length == 1:
                                obj.ctagastoprov = parseInt($scope.ctasGastoProv[0].idcuentac);
                                //console.log(obj);
                                execUpdate(obj);
                                break;
                            case $scope.ctasGastoProv.length > 1:
                                $scope.openSelectCtaGastoProv(obj, 'u');
                                break;
                        }
                    });
                });
            };

            $scope.delCompra = (obj) => {
                $confirm({
                    text: '¿Seguro(a) de eliminar esta factura de compra? (También se eliminará su detalle contable)',
                    title: 'Eliminar factura de compra', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    compraSrvc.editRow({ id: obj.id }, 'd').then(() => {
                        $scope.getLstCompras();
                        $scope.resetCompra();
                    });
                });
            };

            $scope.loadProyectosCompra = function (idcompra) {
                compraSrvc.lstProyectosCompra(+idcompra).then(function (d) {
                    $scope.lstproyectoscompra = d;
                });
            };

            $scope.resetProyectoCompra = function () {
                $scope.proyectocompra = {
                    id: 0, idcompra: $scope.laCompra.id, idproyecto: undefined, idcuentac: undefined, monto: null
                }
                $scope.periodoCerrado = false;
            };

            $scope.getProyectoCompra = function (idproycompra) {
                compraSrvc.getProyectoCompra(+idproycompra).then(function (d) {
                    $scope.proyectocompra = d[0];
                });
            };

            $scope.addProyectoCompra = function (obj) {
                compraSrvc.editRow(obj, 'cd').then(function (d) {
                    $scope.loadProyectosCompra(obj.idcompra);
                    $scope.getProyectoCompra(d.lastid);
                });
            };

            $scope.updProyectoCompra = function (obj) {
                compraSrvc.editRow(obj, 'ud').then(function () {
                    $scope.loadProyectosCompra(obj.idcompra);
                    $scope.getProyectoCompra(obj.id);
                });
            };

            $scope.delProyectoCompra = function (obj) {
                compraSrvc.editRow({ id: obj.id }, 'dd').then(function () {
                    $scope.loadProyectosCompra(obj.idcompra);
                    $scope.resetProyectoCompra();
                });
            };

            $scope.bindCheque = () => {
                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalBindChq.html',
                    controller: 'ModalBindChq',
                    resolve: {
                        compra: () => $scope.laCompra
                    }
                });

                modalInstance.result.then(() => $scope.getCompra($scope.laCompra.id), () => { });
            };

            $scope.printChequesSinFact = () => {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalChequeSinFact.html',
                    controller: 'ModalListChequeSinFactCtrl',
                    windowClass: 'app-modal-window',
                    resolve: {
                        cheques: () => $scope.losCheques
                    }
                });

                modalInstance.result.then(function (obj) {
                    $scope.laCompra.idcheque = obj.id;
                    $scope.laCompra.totfact = obj.monto.toString().replace(',', '');
                    $scope.laCompra.objMoneda = $scope.monedas.find(m => +m.id === +obj.idmoneda);
                    $scope.laCompra.conceptomayor = obj.concepto;
                    $scope.laCompra.tipocambio = obj.tipocambio;
                    $scope.calcular();
                }, function () { return 0; });
            };

            $scope.printFacturas = () => {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalNotasCredito.html',
                    controller: 'ModalListFacturasCtrl',
                    windowClass: 'app-modal-window',
                    resolve: {
                        facturas: () => $scope.lasFacturas
                    }
                });

                modalInstance.result.then(function (obj) {
                    $scope.laCompra.iddocliquida = obj.id;
                    $scope.laCompra.totfact = obj.saldo.toString().replace(',', '');
                    $scope.laCompra.objMoneda = $scope.monedas.find(m => +m.id === +obj.idmoneda);
                    $scope.laCompra.idproyecto = obj.idproyecto;
                    $scope.laCompra.tipocambio = obj.tipocambio;
                    $scope.laCompra.ordentrabajo = obj.ordentrabajo;
                });
            };

            $scope.setInfoContador = () => {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalContadores.html',
                    controller: 'ModalContadoresCtrl',
                    windowClass: 'app-modal-window',
                    resolve: {
                        servicios: () => $scope.servicios,
                        laCompra: () => $scope.laCompra
                    }
                });
                
                modalInstance.result.then(function (obj) {
                    $scope.laCompra.idservicio = obj.idservicio;
                    $scope.laCompra.lecturaini = obj.lecturaini;
                    $scope.laCompra.lecturafin = obj.lecturafin;
                    $scope.laCompra.preciouni = obj.precio;
                    $scope.laCompra.fini = obj.fini;
                    $scope.laCompra.ffin = obj.ffin;
                });
            };

            $scope.zeroDebe = function (valor) { $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
            $scope.zeroHaber = function (valor) { $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

            $scope.loadDetaCont = function () {
                $scope.losDetCont = [];
                detContSrvc.lstDetalleCont($scope.origen, +$scope.laCompra.id).then(function (detc) {
                    $scope.losDetCont = procDataDet(detc);
                    $scope.elDetCont = { debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined };
                });
            };

            $scope.addDetCont = function (obj) {
                obj.origen = $scope.origen;
                obj.idorigen = parseInt($scope.laCompra.id);
                obj.debe = parseFloat(obj.debe);
                obj.haber = parseFloat(obj.haber);
                obj.idcuenta = parseInt(obj.objCuenta.id);
                detContSrvc.editRow(obj, 'c').then(function () {
                    detContSrvc.lstDetalleCont($scope.origen, parseInt($scope.laCompra.id)).then(function (detc) {
                        $scope.losDetCont = procDataDet(detc);
                        $scope.elDetCont = { debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined };
                        $scope.searchcta = "";
                    });
                });
            };

            $scope.updDetCont = function (obj) {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalUpdDetCont.html',
                    controller: 'ModalUpdDetContCtrl',
                    resolve: {
                        detalle: function () { return obj; },
                        idempresa: function () { return +$scope.laCompra.idempresa; }
                    }
                });

                modalInstance.result.then(function () {
                    $scope.loadDetaCont();
                }, function () { $scope.loadDetaCont(); });
            };

            $scope.delDetCont = function (obj) {
                $confirm({ text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No' }).then(function () {
                    detContSrvc.editRow({ id: obj.id }, 'd').then(function () { $scope.getDetCont(obj.idorigen); });
                });
            };

            //$scope.printVersion = function(){ PrintElem('#toPrint', 'Factura de compra'); };
            $scope.printVersion = function (obj) {

                var test = false;

                jsReportSrvc.getPDFReport(test ? '' : 'Hyh6Ta31z', { idcompra: obj.id }).then(function (pdf) { $window.open(pdf); });


            };

        }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalCtasGastoProvCtrl', ['$scope', '$uibModalInstance', 'lstctasgasto', function ($scope, $uibModalInstance, lstctasgasto) {
        $scope.lasCtasGasto = lstctasgasto;
        $scope.selectedCta = [];

        $scope.ok = function () {
            $uibModalInstance.close($scope.selectedCta[0]);
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalISR', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc', function ($scope, $uibModalInstance, compra, compraSrvc) {
        $scope.compra = compra;
        $scope.compra.isrlocal = parseFloat(($scope.compra.isr * $scope.compra.tipocambio).toFixed(2));
        //console.log($scope.compra);

        $scope.setMesAnio = function () {
            if (moment($scope.compra.fecpagoformisr).isValid()) {
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
            compraSrvc.editRow($scope.compra, 'uisr').then(function () { $uibModalInstance.close($scope.compra.id); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function ($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm) {
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
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalBindChq', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc', function ($scope, $uibModalInstance, compra, compraSrvc) {
        $scope.compra = compra;
        $scope.lstCheques = [];
        $scope.chqbind = {
            idtranban: undefined,
            idtipodoc: 1,
            documento: $scope.compra.documento,
            fechadoc: moment($scope.compra.fechafactura).format('YYYY-MM-DD'),
            monto: $scope.compra.totfact - $scope.compra.isr,
            serie: $scope.compra.serie,
            iddocto: +$scope.compra.id,
            fechaliquidastr: moment().format('YYYY-MM-DD')
        };

        $scope.loadCheques = () => compraSrvc.getChequesProveedor({
            idproveedor: $scope.compra.idproveedor, idempresa: $scope.compra.idempresa, idmoneda: $scope.compra.idmoneda, idcompra: $scope.compra.id
        }).then((data) => $scope.lstCheques = data);

        $scope.ok = function () {
            // console.log('TRANBAN:', $scope.chqbind);
            compraSrvc.editRow($scope.chqbind, 'addtotranban').then(() => $uibModalInstance.close($scope.compra.id));
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.loadCheques();

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalListChequeSinFactCtrl', ['$scope', '$uibModalInstance', 'cheques', function ($scope, $uibModalInstance, cheques) {
        $scope.cheques = cheques;


        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.ok = (chq) => $uibModalInstance.close(chq);


    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalListFacturasCtrl', ['$scope', '$uibModalInstance', 'facturas', function ($scope, $uibModalInstance, facturas) {
        $scope.facturas = facturas;


        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.ok = (fact) => $uibModalInstance.close(fact);


    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalContadoresCtrl', ['$scope', '$uibModalInstance', 'servicios', 'laCompra', function ($scope, $uibModalInstance, servicios, laCompra) {
        $scope.servicios = servicios;
        $scope.compra = laCompra;
        $scope.obj = { idservicio: undefined, lecturaini: undefined, lecturafin: undefined, precio: undefined }; 

        $scope.obj.idservicio = $scope.compra.idservicio !== null && $scope.compra.idservicio !== undefined ? $scope.compra.idservicio : undefined;
        $scope.obj.lecturaini = $scope.compra.lecturaini !== null && $scope.compra.lecturaini !== undefined ? $scope.compra.lecturaini : undefined;
        $scope.obj.lecturafin = $scope.compra.lecturafin !== null && $scope.compra.lecturafin !== undefined ? $scope.compra.lecturafin : undefined;
        $scope.obj.precio = $scope.compra.preciouni !== null && $scope.compra.preciouni !== undefined ? $scope.compra.preciouni : undefined;
        $scope.obj.ffin = $scope.compra.ffin !== null && $scope.compra.ffin !== undefined ? $scope.compra.ffin : undefined;
        $scope.obj.fini = $scope.compra.fini !== null && $scope.compra.fini !== undefined ? $scope.compra.fini : undefined;

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.ok = (obj) => $uibModalInstance.close(obj);


    }]);

}());
