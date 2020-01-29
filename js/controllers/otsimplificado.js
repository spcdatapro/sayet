(function () {

    var otsimplificadoctrl = angular.module('cpm.otsimplificadoctrl', []);

    otsimplificadoctrl.controller('otSimplificadoCtrl', ['$scope', '$route', '$filter', 'presupuestoSrvc', 'proyectoSrvc', 'empresaSrvc', 'tipogastoSrvc', 'monedaSrvc', 'tranBancSrvc', 'authSrvc', ($scope, $route, $filter, presupuestoSrvc, proyectoSrvc, empresaSrvc, tipogastoSrvc, monedaSrvc, tranBancSrvc, authSrvc) => {
        $scope.presupuesto = {};
        $scope.lstpresupuestos = [];
        $scope.ot = {};
        $scope.lstot = [];

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
        $scope.fltrot = { fdel: moment('2017-10-01').toDate(), fal: moment().endOf('month').toDate(), idestatuspresup: null, idusuario: 0 };
        $scope.lstestatuspresup = [];
        $scope.loadingPresupuestos = false;

        proyectoSrvc.lstProyecto().then((d) => $scope.proyectos = d);
        empresaSrvc.lstEmpresas().then((d) => $scope.empresas = d);
        tipogastoSrvc.lstTipogastos().then((d) => $scope.tiposgasto = d);
        monedaSrvc.lstMonedas().then((d) => $scope.monedas = d);
        tranBancSrvc.lstBeneficiarios().then((d) => $scope.proveedores = d);

        authSrvc.getSession().then((usrLogged) => {
            $scope.usrdata = usrLogged;
            $scope.fltrot.idusuario = $scope.usrdata.uid;
            authSrvc.gpr({ idusuario: parseInt(usrLogged.uid), ruta: $route.current.params.name }).then((d) => $scope.permiso = d);
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

        $scope.setEmpresa = (item) => $scope.presupuesto.empresa = item.idempresa;

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
            $scope.lstot = [];
            presupuestoSrvc.getPresupuesto(idpresupuesto).then((d) => {
                $scope.presupuesto = procDataPresup(d)[0];
                $scope.presupuesto.proyecto = $scope.presupuesto.idproyecto;
                $scope.presupuesto.empresa = $scope.presupuesto.idempresa;
                $scope.loadSubtTiposGasto($scope.presupuesto.idtipogasto);
                // $scope.getLstOts(idpresupuesto);
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

    }]);
}());
