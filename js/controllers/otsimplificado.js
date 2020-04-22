(function () {

    var otsimplificadoctrl = angular.module('cpm.otsimplificadoctrl', []);

    otsimplificadoctrl.controller('otSimplificadoCtrl', [
        '$scope', '$route', '$filter', 'presupuestoSrvc', 'proyectoSrvc', 'empresaSrvc', 'tipogastoSrvc', 'monedaSrvc', 'tranBancSrvc', 'authSrvc', 'compraSrvc', 'toaster', 'proveedorSrvc', '$uibModal', 'detContSrvc',
        '$confirm', 'tipoFacturaSrvc', 'tipoCompraSrvc', 'tipoCombustibleSrvc',
        ($scope, $route, $filter, presupuestoSrvc, proyectoSrvc, empresaSrvc, tipogastoSrvc, monedaSrvc, tranBancSrvc, authSrvc, compraSrvc, toaster, proveedorSrvc, $uibModal, detContSrvc,
            $confirm, tipoFacturaSrvc, tipoCompraSrvc, tipoCombustibleSrvc) => {

            $scope.presupuesto = {};
            $scope.lstpresupuestos = [];
            $scope.ot = {};
            $scope.lstots = [];
            $scope.params = {
                fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), tipo: 1, idusuario: 0
            };

            $scope.proyectos = [];
            $scope.empresas = [];
            $scope.tiposgasto = [];
            $scope.monedas = [];
            $scope.proveedores = [];
            $scope.subtiposgasto = [];
            $scope.sl = { presupuesto: true, ot: true };
            $scope.usrdata = {};
            $scope.permiso = {};
            $scope.lbl = { presupuesto: '', ot: '' };

            $scope.grpBtnPresupuesto = { i: false, p: false, e: false, u: false, c: false, d: false, a: true };
            $scope.grpBtnOt = { i: false, p: false, e: false, u: false, c: false, d: false, a: true };
            $scope.showForm = { presupuesto: false, ot: false };
            $scope.lstestatuspresup = [];
            $scope.loadingPresupuestos = false;
            $scope.lstdetpagos = [];
            $scope.fpago = {};

            proyectoSrvc.lstProyecto().then((d) => $scope.proyectos = d);
            empresaSrvc.lstEmpresas().then((d) => $scope.empresas = d);
            tipogastoSrvc.lstTipogastos().then((d) => $scope.tiposgasto = d);
            monedaSrvc.lstMonedas().then((d) => $scope.monedas = d);
            tranBancSrvc.lstBeneficiarios().then((d) => $scope.proveedores = d);

            authSrvc.getSession().then((usrLogged) => {
                $scope.usrdata = usrLogged;
                $scope.params.idusuario = $scope.usrdata.uid;
                authSrvc.gpr({ idusuario: parseInt(usrLogged.uid), ruta: $route.current.params.name }).then((d) => $scope.permiso = d);
                $scope.loadOts('1,2,3');
            });

            $scope.loadSubtTiposGasto = (idtipogasto) => tipogastoSrvc.lstSubTipoGastoByTipoGasto(+idtipogasto).then((d) => $scope.subtiposgasto = d);

            $scope.confGrpBtn = (grp, i, u, d, a, e, c, p) => {
                const instruccion = `
                $scope.${grp}.i = i; 
                $scope.${grp}.u = u; 
                $scope.${grp}.d = d; 
                $scope.${grp}.a = a; 
                $scope.${grp}.e = e; 
                $scope.${grp}.c = c; 
                $scope.${grp}.p = p;
            `;
                eval(instruccion);
            };

            $scope.loadOts = (idestatuspresup) => {
                $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
                $scope.params.idestatuspresup = !!idestatuspresup ? idestatuspresup : '';
                presupuestoSrvc.lstPresupuestos($scope.params).then(d => { $scope.lstots = d; });
            };

            $scope.setEmpresa = (item) => $scope.presupuesto.empresa = item.idempresa;

            $scope.setOrigenProv = (item) => $scope.presupuesto.origenprov = +item.dedonde;

            $scope.resetPresupuesto = () => {
                $scope.presupuesto = { tipo: '1', fechasolicitud: moment().toDate(), idmoneda: '1', tipocambio: 1.00, coniva: 1, tipodocumento: 1 };
                $scope.ot = {};
                $scope.lstot = [];
                $scope.srchproy = '';
                $scope.srchemp = '';
                $scope.lbl.presupuesto = '';
                $scope.lbl.ot = '';
            };

            procDataPresup = (data) => {
                const tmpData = data.map(d => {
                    d.id = parseInt(d.id);
                    d.idusuario = parseInt(d.idusuario);
                    d.idestatuspresupuesto = parseInt(d.idestatuspresupuesto);
                    d.idusuarioaprueba = parseInt(d.idusuarioaprueba);
                    d.total = parseFloat(parseFloat(d.total).toFixed(2));
                    d.gastado = parseFloat(parseFloat(d.gastado).toFixed(2));
                    d.fechasolicitud = moment(d.fechasolicitud).toDate();
                    d.fechacreacion = moment(d.fechacreacion).toDate();
                    d.fhenvioaprobacion = moment(d.fhenvioaprobacion).isValid() ? moment(d.fhenvioaprobacion).toDate() : null;
                    d.fhaprobacion = moment(d.fhaprobacion).isValid() ? moment(d.fhaprobacion).toDate() : null;
                    d.origenprov = parseInt(d.origenprov);
                    d.coniva = +d.coniva;
                    return d;
                });
                $scope.loadingPresupuestos = false;
                return tmpData;
            }

            $scope.getPresupuesto = (idpresupuesto) => {
                $scope.ot = {};
                //$scope.lstot = [];
                presupuestoSrvc.getPresupuesto(idpresupuesto).then((d) => {
                    $scope.presupuesto = procDataPresup(d)[0];
                    $scope.presupuesto.proyecto = $scope.presupuesto.idproyecto;
                    $scope.presupuesto.empresa = $scope.presupuesto.idempresa;
                    $scope.loadSubtTiposGasto($scope.presupuesto.idtipogasto);
                    $scope.getLstOts(idpresupuesto);

                    switch (+$scope.presupuesto.tipodocumento) {
                        case 1: $scope.getCompra(0); break;
                        // case 2 : break;
                    }

                    $scope.lbl.presupuesto = 'No. ' + $scope.presupuesto.id + ' - ' + ($filter('getById')($scope.proyectos, $scope.presupuesto.idproyecto)).nomproyecto + ' - ';
                    $scope.lbl.presupuesto += ($filter('getById')($scope.empresas, $scope.presupuesto.idempresa)).nomempresa + ' - ';
                    $scope.lbl.presupuesto += ($filter('getById')($scope.tiposgasto, $scope.presupuesto.idtipogasto)).desctipogast + ' - ';
                    $scope.lbl.presupuesto += ($filter('getById')($scope.monedas, $scope.presupuesto.idmoneda)).simbolo + ' ';
                    $scope.lbl.presupuesto += $filter('number')($scope.presupuesto.total, 2);
                    $scope.confGrpBtn('grpBtnPresupuesto', false, false, true, true, true, false, false);
                    $scope.sl.presupuesto = true;
                    goTop();
                });
            };

            setPresupuesto = (obj) => {
                obj.idproyecto = obj.proyecto;
                obj.idempresa = obj.empresa;
                obj.fechasolicitudstr = moment(obj.fechasolicitud).format('YYYY-MM-DD');
                obj.notas = !!obj.notas ? obj.notas : '';
                obj.idusuario = $scope.usrdata.uid;
                obj.tipo = !!obj.tipo ? obj.tipo : 1;
                obj.idproveedor = !!obj.idproveedor ? obj.idproveedor : 0;
                obj.origenprov = !!obj.origenprov ? obj.origenprov : 0;
                obj.idsubtipogasto = !!obj.idsubtipogasto ? obj.idsubtipogasto : 0;
                obj.coniva = !!obj.coniva ? obj.coniva : 1;
                obj.monto = !!obj.monto ? obj.monto : 0.00;
                obj.tipocambio = !!obj.tipocambio ? obj.tipocambio : 1.0000;
                obj.tipodocumento = !!obj.tipodocumento ? obj.tipodocumento : 1;
                return obj;
            }

            $scope.addPresupuesto = (obj) => {
                obj = setPresupuesto(obj);
                presupuestoSrvc.editRow(obj, 'c').then((d) => {
                    // $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(parseInt(d.lastid));
                    $scope.srchproy = '';
                    $scope.srchemp = '';
                });
            };

            $scope.updPresupuesto = (obj) => {
                obj = setPresupuesto(obj);
                presupuestoSrvc.editRow(obj, 'u').then(() => {
                    // $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(obj.id);
                    $scope.srchproy = '';
                    $scope.srchemp = '';
                });
            };

            $scope.delPresupuesto = (obj) => {
                $confirm({ text: '¿Esta seguro(a) de eliminar el presupuesto No. ' + obj.id + '?', title: 'Eliminar presupuesto', ok: 'Sí', cancel: 'No' }).then(() => {
                    presupuestoSrvc.editRow({ id: obj.id }, 'd').then(() => {
                        // $scope.getLstPresupuestos('1,2,3'); 
                        $scope.resetPresupuesto();
                    });
                });
            };

            $scope.nuevoPresupuesto = () => {
                $scope.sl.presupuesto = false;
                $scope.resetPresupuesto();
                $scope.confGrpBtn('grpBtnPresupuesto', true, false, false, false, false, true, false);
            };

            $scope.cancelEditPresup = () => {
                if ($scope.presupuesto.id > 0) {
                    $scope.getPresupuesto($scope.presupuesto.id);
                } else {
                    $scope.resetPresupuesto();
                }
                $scope.confGrpBtn('grpBtnPresupuesto', false, false, false, true, false, false, false);
                $scope.sl.presupuesto = true;
            };

            $scope.startEditPresup = () => {
                $scope.sl.presupuesto = false;
                $scope.confGrpBtn('grpBtnPresupuesto', false, true, true, false, false, true, false);
                goTop();
            };

            $scope.imprimirPresup = () => { };

            procDataOts = (data) => {
                // console.log('Antes', data);
                const tmpData = data.map(d => {
                    d.id = parseInt(d.id);
                    d.idpresupuesto = parseInt(d.idpresupuesto);
                    d.correlativo = parseInt(d.correlativo);
                    d.coniva = parseInt(d.coniva);
                    d.monto = parseFloat(parseFloat(d.monto).toFixed(2));
                    d.tipocambio = parseFloat(parseFloat(d.tipocambio).toFixed(4));
                    d.excedente = parseFloat(parseFloat(d.excedente).toFixed(2));
                    d.origenprov = parseInt(d.origenprov);
                    return d;
                });
                // console.log('Después', tmpData);
                return tmpData;
            }

            $scope.getLstOts = (idpresupuesto) => presupuestoSrvc.lstOts(idpresupuesto)
                .then(d => $scope.ot = procDataOts(d)[0])
                .then(() => $scope.resetFPago())
                .then(() => $scope.loadFormasPago());

            $scope.resetFPago = () => $scope.fpago = { iddetpresup: $scope.ot.id, quitarisr: 0, isr: 0.00 };

            procDataDet = (d) => {
                $scope.sumporcentaje = 0.0000;
                $scope.sumvalor = 0.00;
                for (var i = 0; i < d.length; i++) {
                    d[i].id = parseInt(d[i].id);
                    d[i].iddetpresup = parseInt(d[i].iddetpresup);
                    d[i].nopago = parseInt(d[i].nopago);
                    d[i].porcentaje = parseFloat(parseFloat(d[i].porcentaje).toFixed(4));
                    $scope.sumporcentaje += d[i].porcentaje;
                    d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                    d[i].isr = parseFloat(parseFloat(d[i].isr).toFixed(2));
                    $scope.sumvalor += d[i].monto;
                }

                if ($scope.sumporcentaje <= 100) {
                    $scope.fpago.porcentaje = d.length > 0 ? (100 - $scope.sumporcentaje) : 100;
                    $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
                } else {
                    $scope.fpago.porcentaje = d.length > 0 ? ($scope.porexcede - $scope.sumporcentaje) : $scope.porexcede;
                    $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.valorexcede) / $scope.porexcede).toFixed(2));
                }

                return d;
            }

            $scope.loadFormasPago = () => presupuestoSrvc.lstDetPagoOt($scope.ot.id).then((d) => $scope.lstdetpagos = procDataDet(d));

            $scope.getFormaPago = (obj) => presupuestoSrvc.getDetPagoOt(obj.id).then((d) => $scope.fpago = procDataDet(d)[0]);

            prepFormaPago = (obj) => {
                obj.isr = !!obj.isr ? obj.isr : 0;
                obj.quitarisr = !!obj.quitarisr ? obj.quitarisr : 0;
                obj.notas = !!obj.notas ? obj.notas : '';
                return obj;
            }

            $scope.addFormaPago = (obj) => {
                obj = prepFormaPago(obj);
                presupuestoSrvc.editRow(obj, 'cdp').then(() => {
                    $scope.loadFormasPago();
                    $scope.resetFPago();
                });
            };

            $scope.updFormaPago = (obj) => {
                obj = prepFormaPago(obj);
                presupuestoSrvc.editRow(obj, 'udp').then(() => {
                    $scope.loadFormasPago();
                    $scope.resetFPago();
                });
            };

            $scope.delFormaPago = (obj) => {
                $confirm({ text: '¿Esta seguro(a) de eliminar la forma de pago No. ' + obj.nopago + '?', title: 'Eliminar forma de pago', ok: 'Sí', cancel: 'No' }).then(() => {
                    presupuestoSrvc.editRow({ id: obj.id }, 'ddp').then(() => { $scope.loadFormasPago(); $scope.resetFPago(); });
                });
            };

            /* ---------------------------------------------------------- Aquí empieza lo relacionado con compras ---------------------------------------------------------- */

            $scope.laCompra = { galones: 0.00, idp: 0.00, ordentrabajo: +$scope.presupuesto.id };
            $scope.losProvs = [];
            $scope.lsttiposfact = [];
            $scope.losTiposCompra = [];
            $scope.combustibles = [];

            proveedorSrvc.lstProveedores().then((d) => $scope.losProvs = d);
            tipoFacturaSrvc.lstTiposFactura().then((d) => {
                const tmp = d.map(o => {
                    o.id = +o.id;
                    o.paracompra = +o.paracompra;
                    return o;
                });
                $scope.lsttiposfact = tmp;
            });
            tipoCompraSrvc.lstTiposCompra().then((d) => {
                const tmp = d.map(o => {
                    o.id = +o.id;
                    return o;
                });
                $scope.losTiposCompra = tmp;
            });
            tipoCombustibleSrvc.lstTiposCombustible().then((d) => {
                const tmp = d.map(o => {
                    o.id = +o.id;
                    o.impuesto = parseFloat(parseFloat(o.impuesto).toFixed(2));
                    return o;
                });
                $scope.combustibles = tmp;
            });

            $scope.resetCompra = function () {
                $scope.laCompra = {
                    fechaingreso: moment().toDate(), mesiva: moment().month() + 1, fechafactura: moment().toDate(), creditofiscal: 0, extraordinario: 0, noafecto: 0.0,
                    idempresa: +$scope.presupuesto.idempresa, objMoneda: {}, tipocambio: 1, isr: 0.00, galones: 0.00, idp: 0.00, objTipoCombustible: {},
                    totfact: 0.00, subtotal: 0.00, iva: 0.00, ordentrabajo: +$scope.presupuesto.id, idproyecto: $scope.presupuesto.idproyecto, idunidad: undefined, nombrerecibo: undefined
                };
                // $scope.search = "";
                // $scope.facturastr = '';
                $scope.losDetCont = [];
                $scope.tranpago = [];
                $scope.yaPagada = false;
                $scope.editando = false;
                /*
                monedaSrvc.getMoneda(parseInt($scope.laCompra.objEmpresa.idmoneda)).then(function(m){
                    $scope.laCompra.objMoneda = m[0];
                    $scope.laCompra.tipocambio = parseFloat(m[0].tipocambio).toFixed($scope.dectc);
                });
                */
                $scope.periodoCerrado = false;
                $scope.unidades = [];
                $scope.loadUnidadesProyecto(+$scope.presupuesto.idproyecto);
                $scope.laCompra.objProveedor = $filter('getById')($scope.losProvs, $scope.presupuesto.idproveedor);
                goTop();
            };

            esCombustible = () => {
                if (!!$scope.laCompra.objTipoCompra) {
                    if (!!$scope.laCompra.objTipoCompra.id) {
                        if (parseInt($scope.laCompra.objTipoCompra.id) == 3) {
                            return true;
                        }
                    }

                }
                return false;
            }

            calcIDP = (genidp) => {
                if (genidp && !!$scope.laCompra.objTipoCombustible) {
                    var galones = !!$scope.laCompra.galones ? parseFloat($scope.laCompra.galones) : 0.00;
                    var impuesto = !!$scope.laCompra.objTipoCombustible.impuesto ? parseFloat($scope.laCompra.objTipoCombustible.impuesto) : 0.00;
                    return (galones * impuesto).toFixed(2);
                }
                return 0.00;
            }

            $scope.calcular = () => {
                let geniva = true;
                let genidp = esCombustible();
                let totFact = !!$scope.laCompra.totfact ? parseFloat($scope.laCompra.totfact) : 0;
                let noAfecto = !!$scope.laCompra.noafecto ? parseFloat($scope.laCompra.noafecto) : 0;
                let exento = 0.00, subtotal = 0.00;

                if (!!$scope.laCompra.objTipoFactura) { geniva = parseInt($scope.laCompra.objTipoFactura.generaiva) === 1; }

                $scope.laCompra.idp = calcIDP(genidp);

                exento = parseFloat($scope.laCompra.idp) + noAfecto;
                subtotal = totFact - exento;

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
            };

            $scope.proyectoSelected = (item) => $scope.loadUnidadesProyecto(item.id);

            setObjCompra = (obj) => {
                obj.idempresa = +$scope.presupuesto.idempresa;
                obj.ordentrabajo = +$scope.presupuesto.id;
                obj.idproveedor = parseInt(obj.objProveedor.id);
                obj.conceptoprov = obj.objProveedor.concepto;
                obj.idtipocompra = parseInt(obj.objTipoCompra.id);
                obj.creditofiscal = !!obj.creditofiscal ? obj.creditofiscal : 0;
                obj.extraordinario = !!obj.extraordinario ? obj.extraordinario : 0;
                obj.ordentrabajo = $scope.presupuesto.id;
                obj.fechaingresostr = dateToStr(obj.fechaingreso);
                obj.fechafacturastr = dateToStr(obj.fechafactura);
                obj.fechapagostr = dateToStr(obj.fechapago);
                obj.idmoneda = parseInt(obj.objMoneda.id);
                obj.idtipofactura = parseInt(obj.objTipoFactura.id);
                obj.idtipocombustible = !!obj.objTipoCombustible ? (!!obj.objTipoCombustible.id ? obj.objTipoCombustible.id : 0) : 0;
                obj.idunidad = !!obj.idunidad ? +obj.idunidad : 0;
                //obj.idtipocombustible = 0;
                //obj.idproyecto = 0;
                return obj;
            }

            $scope.addCompra = (obj) => {
                obj = setObjCompra(obj);

                proveedorSrvc.getLstCuentasCont(obj.idproveedor, obj.idempresa).then((lstCtas) => {
                    $scope.ctasGastoProv = lstCtas;
                    switch (true) {
                        case $scope.ctasGastoProv.length == 0:
                            obj.ctagastoprov = 0;
                            //console.log(obj);
                            execCreate(obj);
                            break;
                        case $scope.ctasGastoProv.length == 1:
                            obj.ctagastoprov = parseInt($scope.ctasGastoProv[0].idcuentac);
                            //console.log(obj);
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
                    if (obj.nombrerecibo == null || obj.nombrerecibo == undefined) {
                        delete obj.nombrerecibo;
                    }
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

            execCreate = (obj) => {
                compraSrvc.editRow(obj, 'c').then((d) => {
                    if (+d.lastid > 0) {
                        // $scope.getLstCompras();
                        $scope.getCompra(parseInt(d.lastid));
                    } else {
                        toaster.pop({
                            type: 'error', title: 'Error en la creación de la factura.',
                            body: 'La factura de este proveedor no pudo ser creada. Favor verifique que los datos estén bien ingresados y que la factura de este proveedor no exista.', timeout: 9000
                        });
                    }
                });
            }

            execUpdate = (obj) => {
                compraSrvc.editRow(obj, 'u').then((d) => {
                    // $scope.getLstCompras();
                    $scope.getCompra(parseInt(d.lastid));
                });
            }

            $scope.openSelectCtaGastoProv = (obj, op) => {
                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalSelectCtaGastoProv.html',
                    controller: 'ModalCtasGastoProvCtrl',
                    resolve: {
                        lstctasgasto: () => $scope.ctasGastoProv
                    }
                });

                modalInstance.result.then((selectedItem) => {
                    obj.ctagastoprov = selectedItem.idcuentac;
                    switch (op) {
                        case 'c': execCreate(obj); break;
                        case 'u': execUpdate(obj); break;
                    }
                }, () => { });
            };

            $scope.getCompra = (idcomp) => {
                $scope.losDetCont = [];
                $scope.elDetCont = { debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined };
                compraSrvc.getCompra(idcomp, +$scope.presupuesto.id).then((d) => {
                    if (d.length > 0) {
                        $scope.laCompra = procDataCompras(d)[0];
                        $scope.laCompra.objProveedor = $filter('getById')($scope.losProvs, $scope.laCompra.idproveedor);
                        $scope.laCompra.objMoneda = $filter('getById')($scope.monedas, $scope.laCompra.idmoneda);
                        $scope.laCompra.objTipoFactura = $filter('getById')($scope.lsttiposfact, $scope.laCompra.idtipofactura);
                        $scope.laCompra.objTipoCombustible = $filter('getById')($scope.combustibles, $scope.laCompra.idtipocombustible);
                        $scope.search = $scope.laCompra.objProveedor.nitnombre;
                        tipoCompraSrvc.getTipoCompra($scope.laCompra.idtipocompra).then((tc) => $scope.laCompra.objTipoCompra = tc[0]);
                        $scope.editando = true;
                        cuentacSrvc.getByTipo($scope.laCompra.idempresa, 0).then((d) => $scope.lasCtasMov = d);
                        $scope.loadUnidadesProyecto($scope.laCompra.idproyecto);
                        $scope.getDetCont(idcomp);
                        $scope.loadProyectosCompra(idcomp);
                        $scope.resetProyectoCompra();
                        empresaSrvc.getEmpresa(parseInt($scope.laCompra.idempresa)).then((d) => $scope.laCompra.objEmpresa = d[0]);
                        compraSrvc.getTransPago(idcomp).then((d) => {
                            for (var i = 0; i < d.length; i++) {
                                d[i].idtranban = parseInt(d[i].idtranban);
                                d[i].numero = parseInt(d[i].numero);
                                d[i].monto = parseFloat(d[i].monto);
                            }
                            $scope.tranpago = d;
                            $scope.yaPagada = $scope.tranpago.length > 0;
                        });

                        if ($scope.laCompra.isr > 0) {
                            if ($scope.laCompra.noformisr == '' || $scope.laCompra.noformisr == undefined || $scope.laCompra.noformisr == null) {
                                $scope.modalISR();
                            }
                        }

                        /*//Esto hay que ver si se agrega de nuevo...
                        var tmp = $scope.laCompra, coma = ', ';
    
                        $scope.facturastr = tmp.nomproveedor + coma + tmp.siglas + '-' + tmp.serie + '-' + tmp.documento + coma + moment(tmp.fechafactura).format('DD/MM/YYYY') + coma + tmp.desctipocompra + coma + 'Total: ' + tmp.moneda + ' ';
                        $scope.facturastr += formatoNumero(tmp.totfact, 2) + coma + 'No afecto: ' + tmp.moneda + ' ' + formatoNumero(tmp.noafecto, 2) + coma + ' Subtotal: ' + tmp.moneda + ' ' + formatoNumero(tmp.subtotal, 2) + coma;
                        $scope.facturastr += 'I.V.A.: ' + tmp.moneda + ' ' + formatoNumero(tmp.iva, 2) + coma + 'I.S.R.: ' + tmp.moneda + ' ' + formatoNumero(tmp.isr, 2) + coma + 'I.D.P.: ' + tmp.moneda + ' ' + formatoNumero(tmp.idp, 2);
                        */

                        goTop();
                    } else {
                        $scope.resetCompra();
                    }
                });
            };

            $scope.loadUnidadesProyecto = (idproyecto) => proyectoSrvc.lstUnidadesProyecto(+idproyecto).then((d) => $scope.unidades = d);

            $scope.getDetCont = (idcomp) => detContSrvc.lstDetalleCont($scope.origen, parseInt(idcomp)).then((detc) => $scope.losDetCont = procDataDet(detc));

            $scope.loadProyectosCompra = (idcompra) => compraSrvc.lstProyectosCompra(+idcompra).then((d) => $scope.lstproyectoscompra = d);

            $scope.resetProyectoCompra = () => {
                $scope.proyectocompra = { id: 0, idcompra: $scope.laCompra.id, idproyecto: undefined, idcuentac: undefined, monto: null };
                $scope.periodoCerrado = false;
            };

            $scope.modalISR = () => {
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalISR.html',
                    controller: 'ModalISR',
                    resolve: {
                        compra: () => $scope.laCompra
                    }
                });

                modalInstance.result.then((idcompra) => $scope.getCompra(parseInt(idcompra)), () => { });
            };

            $scope.delCompra = (obj) => {
                $confirm({
                    text: '¿Seguro(a) de eliminar esta factura de compra? (También se eliminará su detalle contable)',
                    title: 'Eliminar factura de compra', ok: 'Sí', cancel: 'No'
                }).then(() => {
                    compraSrvc.editRow({ id: obj.id }, 'd').then(() => {
                        // $scope.getLstCompras();
                        $scope.resetCompra();
                    });
                });
            };

            /* ---------------------------------------------------------- Aquí finaliza lo relacionado con compras ---------------------------------------------------------- */

        }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    otsimplificadoctrl.controller('ModalCtasGastoProvCtrl', ['$scope', '$uibModalInstance', 'lstctasgasto', function ($scope, $uibModalInstance, lstctasgasto) {
        $scope.lasCtasGasto = lstctasgasto;
        $scope.selectedCta = [];

        $scope.ok = () => $uibModalInstance.close($scope.selectedCta[0]);

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//        
    otsimplificadoctrl.controller('ModalISR', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc', function ($scope, $uibModalInstance, compra, compraSrvc) {
        $scope.compra = compra;
        $scope.compra.isrlocal = parseFloat(($scope.compra.isr * $scope.compra.tipocambio).toFixed(2));
        //console.log($scope.compra);

        $scope.setMesAnio = () => {
            if (moment($scope.compra.fecpagoformisr).isValid()) {
                $scope.compra.mesisr = moment($scope.compra.fecpagoformisr).month() + 1;
                $scope.compra.anioisr = moment($scope.compra.fecpagoformisr).year();
            }
        };

        $scope.ok = () => {
            $scope.compra.noformisr = !!$scope.compra.noformisr ? $scope.compra.noformisr : '';
            $scope.compra.noaccisr = !!$scope.compra.noaccisr ? $scope.compra.noaccisr : '';
            $scope.compra.fecpagoformisrstr = moment($scope.compra.fecpagoformisr).isValid() ? moment($scope.compra.fecpagoformisr).format('YYYY-MM-DD') : '';
            $scope.compra.mesisr = !!$scope.compra.mesisr ? $scope.compra.mesisr : 0;
            $scope.compra.anioisr = !!$scope.compra.anioisr ? $scope.compra.anioisr : 0;
            compraSrvc.editRow($scope.compra, 'uisr').then(() => $uibModalInstance.close($scope.compra.id));
        };

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');
    }]);

}());
