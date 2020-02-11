(function () {

    var otsimplificadoctrl = angular.module('cpm.otsimplificadoctrl', []);

    otsimplificadoctrl.controller('otSimplificadoCtrl', ['$scope', '$route', '$filter', 'presupuestoSrvc', 'proyectoSrvc', 'empresaSrvc', 'tipogastoSrvc', 'monedaSrvc', 'tranBancSrvc', 'authSrvc', ($scope, $route, $filter, presupuestoSrvc, proyectoSrvc, empresaSrvc, tipogastoSrvc, monedaSrvc, tranBancSrvc, authSrvc) => {
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
            var instruccion = `
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
            $scope.presupuesto = { tipo: '1', fechasolicitud: moment().toDate(), idmoneda: '1', tipocambio: 1.00, coniva: 1 };
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

    }]);
}());
