(function () {

    var presupuestoctrl = angular.module('cpm.presupuestoctrl', []);

    presupuestoctrl.controller('presupuestoCtrl', ['$scope', 'presupuestoSrvc', '$confirm', 'proyectoSrvc', 'empresaSrvc', 'tipogastoSrvc', 'monedaSrvc', '$filter', 'authSrvc', 'proveedorSrvc', 'toaster', '$uibModal', 'desktopNotification', 'jsReportSrvc', '$window', 'tranBancSrvc', 'estatusPresupuestoSrvc', '$route', 'Upload', 'tipoCambioSrvc', function ($scope, presupuestoSrvc, $confirm, proyectoSrvc, empresaSrvc, tipogastoSrvc, monedaSrvc, $filter, authSrvc, proveedorSrvc, toaster, $uibModal, desktopNotification, jsReportSrvc, $window, tranBancSrvc, estatusPresupuestoSrvc, $route, Upload, tipoCambioSrvc) {

        //$scope.presupuesto = {fechasolicitud: moment().toDate(), idmoneda: '1', tipocambio: 1.00};
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
        $scope.tipocambiogt = {};
        $scope.sl = { presupuesto: true, ot: true };
        $scope.usrdata = {};
        $scope.permiso = {};
        $scope.lbl = { id: '', proyecto: '', empresa: '', tipgasto: '', montopres: '', motnogas: '', avance: '' };

        $scope.grpBtnPresupuesto = { i: false, p: false, e: false, u: false, c: false, d: false, a: true };
        $scope.grpBtnOt = { i: false, p: false, e: false, u: false, c: false, d: false, a: true };
        $scope.showForm = { presupuesto: false, ot: false };
        $scope.fltrot = { fdel: moment('2017-10-01').toDate(), fal: moment().endOf('month').toDate(), idestatuspresup: null, idusuario: 0 };
        $scope.lstestatuspresup = [];
        $scope.loadingPresupuestos = false;
        $scope.ngIncludeUrl = undefined;
        $scope.ngIncludeUrlTB = undefined;
        $scope.urlGenCheques = 'pages/trangenchqots.html';

        proyectoSrvc.lstProyecto().then(function (d) { $scope.proyectos = d; });
        empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });
        tipogastoSrvc.lstTipogastos().then(function (d) { $scope.tiposgasto = d; });
        monedaSrvc.lstMonedas().then(function (d) { $scope.monedas = d; });
        tipoCambioSrvc.getLastTC().then(function (d) {$scope.tipocambiogt = +d.lasttc;});
        //proveedorSrvc.lstProveedores().then(function(d){ $scope.proveedores = d; });
        tranBancSrvc.lstBeneficiarios().then(function (d) { $scope.proveedores = d; });

        authSrvc.getSession().then(function (usrLogged) {
            $scope.usrdata = usrLogged;
            $scope.fltrot.idusuario = $scope.usrdata.uid;
            authSrvc.gpr({ idusuario: parseInt(usrLogged.uid), ruta: $route.current.params.name }).then(function (d) { $scope.permiso = d; });
            $scope.getLstPresupuestos('1,2,3');
        });

        $scope.$on('$includeContentRequested', (event, url) => {
            event.targetScope.idpresupuesto = $scope.presupuesto.id;
            event.targetScope.idot = $scope.ot.id;
        });

        estatusPresupuestoSrvc.lstEstatusPresupuesto().then(function (d) { $scope.lstestatuspresup = d; });

        $scope.confGrpBtn = function (grp, i, u, d, a, e, c, p) {
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c; $scope." + grp + ".p = p;";
            eval(instruccion);
        };

        function procDataPresup(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].id = parseInt(data[i].id);
                data[i].idusuario = parseInt(data[i].idusuario);
                data[i].idestatuspresupuesto = parseInt(data[i].idestatuspresupuesto);
                data[i].idusuarioaprueba = parseInt(data[i].idusuarioaprueba);
                data[i].total = parseFloat(parseFloat(data[i].total).toFixed(2));
                data[i].gastado = parseFloat(parseFloat(data[i].gastado).toFixed(2));
                data[i].fechasolicitud = moment(data[i].fechasolicitud).toDate();
                data[i].fechacreacion = moment(data[i].fechacreacion).toDate();
                data[i].fhenvioaprobacion = moment(data[i].fhenvioaprobacion).isValid() ? moment(data[i].fhenvioaprobacion).toDate() : null;
                data[i].fhaprobacion = moment(data[i].fhaprobacion).isValid() ? moment(data[i].fhaprobacion).toDate() : null;
                data[i].origenprov = parseInt(data[i].origenprov);
                data[i].coniva = +data[i].coniva;
            }
            $scope.loadingPresupuestos = false;
            return data;
        }

        $scope.getLstPresupuestos = (idestatuspresup) => {
            $scope.fltrot.fdelstr = moment($scope.fltrot.fdel).format('YYYY-MM-DD');
            $scope.fltrot.falstr = moment($scope.fltrot.fal).format('YYYY-MM-DD');
            $scope.fltrot.idestatuspresup = idestatuspresup != null && idestatuspresup !== undefined ? idestatuspresup : '';
            $scope.loadingPresupuestos = true;
            presupuestoSrvc.lstPresupuestos($scope.fltrot).then((d) => {
                $scope.lstpresupuestos = procDataPresup(d);
            });
        };

        $scope.resetPresupuesto = function () {
            $scope.presupuesto = { fechasolicitud: moment().toDate(), idmoneda: '1', tipocambio: 1.00, coniva: 1, escontado: 0 };
            $scope.ot = {};
            $scope.lstot = [];
            $scope.srchproy = '';
            $scope.srchemp = '';
            $scope.lbl.id = '';
            $scope.lbl.proyecto = '';
            $scope.lbl.empresa = '';
            $scope.lbl.tipgasto = '';
            $scope.lbl.montopres = '';
            $scope.lbl.montogas = '';
            $scope.lbl.avance = '';
        };

        function setPresupuesto(obj) {
            obj.idproyecto = obj.proyecto;
            obj.idempresa = obj.empresa;
            obj.fechasolicitudstr = moment(obj.fechasolicitud).format('YYYY-MM-DD');
            obj.notas = obj.notas != null && obj.notas != undefined ? obj.notas : '';
            obj.idusuario = $scope.usrdata.uid;
            obj.tipo = obj.tipo != null && obj.tipo != undefined ? obj.tipo : 1;
            obj.idproveedor = obj.idproveedor != null && obj.idproveedor != undefined ? obj.idproveedor : 0;
            obj.origenprov = obj.origenprov != null && obj.origenprov != undefined ? obj.origenprov : 0;
            obj.idsubtipogasto = obj.idsubtipogasto != null && obj.idsubtipogasto != undefined ? obj.idsubtipogasto : 0;
            obj.coniva = obj.coniva != null && obj.coniva != undefined ? obj.coniva : 1;
            obj.escontado = obj.escontado != null && obj.escontado != undefined ? obj.escontado : 0;
            obj.monto = obj.monto != null && obj.monto != undefined ? obj.monto : 0.00;
            obj.tipocambio = obj.tipocambio != null && obj.tipocambio != undefined ? obj.tipocambio : 1.0000;
            return obj;
        }

        $scope.loadSubtTiposGasto = function (idtipogasto) {
            tipogastoSrvc.lstSubTipoGastoByTipoGasto(+idtipogasto).then(function (d) { $scope.subtiposgasto = d; });
        };

        $scope.getPresupuesto = function (idpresupuesto, movertab) {
            if (movertab == null || movertab == undefined) { movertab = true }
            $scope.ot = {};
            $scope.lstot = [];
            presupuestoSrvc.getPresupuesto(idpresupuesto).then(function (d) {
                $scope.presupuesto = procDataPresup(d)[0];
                $scope.presupuesto.proyecto = $scope.presupuesto.idproyecto;
                $scope.presupuesto.empresa = $scope.presupuesto.idempresa;
                $scope.loadSubtTiposGasto($scope.presupuesto.idtipogasto);
                $scope.getLstOts(idpresupuesto);
                $scope.loadOTAdjuntos(idpresupuesto, 1);
                $scope.lbl.id = $scope.presupuesto.id ;
                $scope.lbl.proyecto = ' Proyecto: ' + ($filter('getById')($scope.proyectos, $scope.presupuesto.idproyecto)).nomproyecto;
                $scope.lbl.empresa = ' Empresa: ' + ($filter('getById')($scope.empresas, $scope.presupuesto.idempresa)).nomempresa;
                $scope.lbl.tipgasto = ' Tipo gasto: ' + ($filter('getById')($scope.tiposgasto, $scope.presupuesto.idtipogasto)).desctipogast;
                $scope.lbl.montopres = ' Monto Presupuestado: ' + ($filter('getById')($scope.monedas, $scope.presupuesto.idmoneda)).simbolo + $scope.presupuesto.montoot;
                $scope.lbl.montogas = ' Monto Gastado: ' + ($filter('getById')($scope.monedas, $scope.presupuesto.idmoneda)).simbolo + $scope.presupuesto.montogastado;
                $scope.lbl.avance = ' Avance: ' + $scope.presupuesto.avanceot;
                $scope.confGrpBtn('grpBtnPresupuesto', false, false, true, true, true, false, false);
                $scope.sl.presupuesto = true;
                if (movertab) {
                    moveToTab('divLstPresup', 'divFrmPresup');
                }
                goTop();
            });
        };


        $scope.cancelEditPresup = function () {
            if ($scope.presupuesto.id > 0) {
                $scope.getPresupuesto($scope.presupuesto.id);
            } else {
                $scope.resetPresupuesto();
            }
            $scope.confGrpBtn('grpBtnPresupuesto', false, false, false, true, false, false, false);
            $scope.sl.presupuesto = true;
        };

        $scope.startEditPresup = function () {
            $scope.sl.presupuesto = false;
            $scope.confGrpBtn('grpBtnPresupuesto', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirPresup = function () { console.log('Función pendiente...') };

        $scope.printPrespuesto = function (idpresupuesto, adetalle) {
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'r1UD2qMnZ' : 'r1cGFmmhZ', { idpresupuesto: idpresupuesto, detallado: adetalle }).then(function (pdf) { $window.open(pdf); });

        };

        $scope.printPrespuestoNue = function (idpresupuesto) {
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'r1UD2qMnZ' : 'S183YxGZ_', { idpresupuesto: idpresupuesto }).then(function (pdf) { $window.open(pdf); });

        };

        $scope.printPrespuestoNueD = function (idpresupuesto) {
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'r1UD2qMnZ' : 'H1w6yuaWd', { idpresupuesto: idpresupuesto }).then(function (pdf) { $window.open(pdf); });

        };

        $scope.printOt = async function (idot, esPresupuesto) {
            let qOt = {};
            if (esPresupuesto) {
                qOt = await presupuestoSrvc.lstOts(idot);
            }
            //console.log(qOt)
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'BJdOgyV2W' : 'S1eAuyN2b', { idot: esPresupuesto ? +qOt[0].id : idot }).then(function (pdf) { $window.open(pdf); });
        };

        $scope.printOtNue = async function (idot, esPresupuesto) {
            let qOt = {};
            if (esPresupuesto) {
                qOt = await presupuestoSrvc.lstOts(idot);
            }
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'BJdOgyV2W' : 'rJPo84G0w', { idot: esPresupuesto ? +qOt[0].id : idot }).then(function (pdf) { $window.open(pdf); });
        };

        $scope.nuevoPresupuesto = function () {
            $scope.sl.presupuesto = false;
            $scope.resetPresupuesto();
            $scope.confGrpBtn('grpBtnPresupuesto', true, false, false, false, false, true, false);
        };

        $scope.setEmpresa = function (item) {
            //console.log(item);
            $scope.presupuesto.empresa = item.idempresa;
        };

        $scope.setOrigenProv = function (item, model) {
            $scope.presupuesto.origenprov = +item.dedonde;
        };

        $scope.addPresupuesto = function (obj) {
            obj = setPresupuesto(obj);
            presupuestoSrvc.editRow(obj, 'c').then(function (d) {
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto(parseInt(d.lastid));
                $scope.srchproy = '';
                $scope.srchemp = '';
            });
        };

        $scope.updPresupuesto = function (obj) {
            obj = setPresupuesto(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'u').then(function (d) {
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto(obj.id);
                $scope.srchproy = '';
                $scope.srchemp = '';
            });
        };

        $scope.delPresupuesto = function (obj) {
            $confirm({ text: '¿Esta seguro(a) de eliminar el presupuesto No. ' + obj.id + '?', title: 'Eliminar presupuesto', ok: 'Sí', cancel: 'No' }).then(function () {
                presupuestoSrvc.editRow({ id: obj.id }, 'd').then(function () { $scope.getLstPresupuestos('1,2,3'); $scope.resetPresupuesto(); });
            });
        };

        $scope.enviar = (obj, idpresupuesto, correlativo) => {
            let numpresup = obj.id;
            obj.esot = 0;
            if (idpresupuesto && correlativo) {
                numpresup = `${idpresupuesto}-${correlativo}`;
                obj.esot = 1;
            }
            $confirm({ text: `¿Esta seguro(a) de enviar el presupuesto No. ${numpresup} para aprobación?`, title: 'Envio de presupuesto', ok: 'Sí', cancel: 'No' }).then(() => {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/ep').then(() => {
                    $scope.getLstPresupuestos('1,2,3');
                    if (obj.esot === 1) {
                        $scope.getLstOts(idpresupuesto);
                    }
                    toaster.pop('info', 'Envio de presupuesto', `Presupuesto No. ${numpresup} enviado a aprobación...`, 'timeout:1500');
                });

            });
        };

        $scope.terminaPresupuesto = (obj, idpresupuesto, correlativo) => {
            let numpresup = obj.id;
            obj.esot = 0;
            if (idpresupuesto && correlativo) {
                numpresup = `${idpresupuesto}-${correlativo}`;
                obj.esot = 1;
            }
            $confirm({ text: `¿Esta seguro(a) de terminar el presupuesto No. ${numpresup}? Si lo termina, ya no podrá modificarlo a menos que lo reaperturen.`, title: 'Terminar presupuesto', ok: 'Sí', cancel: 'No' }).then(function () {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/tp').then(function () {
                    $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(obj.id, true);
                    if (obj.esot === 1) {
                        $scope.getLstOts(idpresupuesto);
                    }
                    toaster.pop('info', 'Terminar presupuesto', `Presupuesto No. ${numpresup} terminado...`, 'timeout:1500');
                });
            });
        };

        $scope.reabrirPresupuesto = (obj, idpresupuesto, correlativo) => {
            let numpresup = obj.id;
            obj.esot = 0;
            if (idpresupuesto && correlativo) {
                numpresup = `${idpresupuesto}-${correlativo}`;
                obj.esot = 1;
            }
            $confirm({ text: `¿Esta seguro(a) de abrir nuevamente el presupuesto No. ${numpresup}?`, title: 'Re-abrir presupuesto', ok: 'Sí', cancel: 'No' }).then(function () {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/rp').then(function () {
                    $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(obj.id, true);
                    if (obj.esot === 1) {
                        $scope.getLstOts(idpresupuesto);
                    }
                    toaster.pop('info', 'Re-abrir presupuesto', `Presupuesto No. ${numpresup} reaperturado...`, 'timeout:1500');
                });
            });
        };

        $scope.anulaPresupuesto = function (obj, idpresupuesto, correlativo) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAnulaPresupuesto.html',
                controller: 'ModalAnulaPresupuesto',
                resolve: {
                    presupuesto: function () { return obj; },
                    usr: function () { return $scope.usrdata; },
                    idpresupuesto: idpresupuesto,
                    correlativo: correlativo
                }
            });
            modalInstance.result.then(function () {
                moveToTab('divFrmPresup', 'divLstPresup');
                $scope.resetPresupuesto();
                $scope.confGrpBtn('grpBtnPresupuesto', false, false, false, true, false, false, false);
                $scope.getLstPresupuestos('1,2,3');
            }, function () { return 0; });
        };

        // $scope.getLstPresupuestos('1,2,3');

        function procDataOts(data) {
            for (var i = 0; i < data.length; i++) {
                data[i].id = parseInt(data[i].id);
                data[i].idpresupuesto = parseInt(data[i].idpresupuesto);
                data[i].correlativo = parseInt(data[i].correlativo);
                data[i].coniva = parseInt(data[i].coniva);
                data[i].monto = parseFloat(parseFloat(data[i].monto).toFixed(2));
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed(4));
                data[i].excedente = parseFloat(parseFloat(data[i].excedente).toFixed(2));
                data[i].origenprov = parseInt(data[i].origenprov);
                data[i].escontado = parseInt(data[i].escontado);

            }
            return data;
        }

        $scope.getLstOts = function (idpresupuesto) {
            presupuestoSrvc.lstOts(idpresupuesto).then(function (d) {
                $scope.lstot = procDataOts(d);
                if (+$scope.presupuesto.tipo === 1 && $scope.lstot.length > 0) {
                    $scope.getOt(d[0].id);
                }
            });
        };

        $scope.loadPaginasComplemento = (correlativo, idot) => {
            const ahora = moment().toDate();
            $scope.urlGenCheques = `pages/trangenchqots.html?upd=${ahora}`;
            $scope.ngIncludeUrlTB = `pages/tranbanc.html?upd=${ahora}`;
            const qTipoDoc = (+correlativo > 0 && +idot > 0) ? +$scope.ot.tipodocumento : +$scope.presupuesto.tipodocumento;
            //console.log(qTipoDoc);
            switch (qTipoDoc) {
                case 1: $scope.ngIncludeUrl = `pages/tranfactcompra.html?upd=${ahora}`; break;
                case 2: $scope.ngIncludeUrl = `pages/tranreembolso.html?upd=${ahora}`; break;
                default: $scope.ngIncludeUrl = undefined;
            }
        }

        $scope.getOt = function (idot) {
            presupuestoSrvc.getOt(idot).then(function (d) {
                $scope.ot = procDataOts(d)[0];
                $scope.confGrpBtn('grpBtnOt', false, false, true, true, true, false, false);
                $scope.sl.ot = true;
                $scope.showForm.ot = true;
                $scope.loadPaginasComplemento($scope.ot.correlativo, $scope.ot.id);
                $scope.loadOTAdjuntos();
                goTop();
            });
        };

        $scope.resetOt = function () {
            $scope.ot = { idpresupuesto: $scope.presupuesto.id, escontado: 0, coniva: 1, monto: 0.00, idproveedor: undefined, idsubtipogasto: undefined, tipocambio: 1.0000, origenprov: 0 }
        };

        $scope.cancelEditOt = function () {
            if ($scope.ot.id > 0) {
                $scope.getOt($scope.ot.id);
            } else {
                $scope.resetOt();
            }
            $scope.confGrpBtn('grpBtnOt', false, false, false, true, false, false, false);
            $scope.sl.ot = true;
        };

        $scope.startEditOt = function () {
            $scope.sl.ot = false;
            $scope.confGrpBtn('grpBtnOt', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirOt = function () { console.log('Función pendiente...') };

        $scope.nuevaOt = function () {
            $scope.sl.ot = false;
            $scope.resetOt();
            $scope.confGrpBtn('grpBtnOt', true, false, false, false, false, true, false);
        };

        $scope.tryNotify = function () {
            desktopNotification.show('PRUEBA DE NOTIFICACIONES!!!', {
                icon: 'img/sayet.ico',
                body: 'HOLA!!!!',
                onClick: function () {
                    console.log('Clicked on notification...')
                }
            });
        };

        function setDataOt(obj) {
            obj.idpresupuesto = $scope.presupuesto.id;
            obj.idusuario = $scope.usrdata.uid;
            return obj;
        }

        $scope.setOrigenProvOt = function (item, model) {
            $scope.ot.origenprov = +item.dedonde;
            $scope.ot.retieneisr = +item.retieneisr;
        };

        $scope.addOt = function (obj) {
            obj = setDataOt(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'cd').then(function (d) {
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto($scope.presupuesto.id, false);
                $scope.getLstOts($scope.presupuesto.id);
                $scope.getOt(parseInt(d.lastid));
            });
        };

        $scope.updOt = function (obj) {
            obj = setDataOt(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'ud').then(function (d) {
                $scope.getLstOts($scope.presupuesto.id);
                $scope.getOt(obj.id);
            });
        };

        $scope.delOt = (obj) => {
            $confirm({ text: '¿Esta seguro(a) de eliminar la OT No. ' + obj.idpresupuesto + '-' + obj.correlativo + '?', title: 'Eliminar OT', ok: 'Sí', cancel: 'No' }).then(() => {
                presupuestoSrvc.editRow({ id: obj.id }, 'dd').then(() => { $scope.getLstOts($scope.presupuesto.id); $scope.resetOt(); });
            });
        };

        $scope.verDetPagos = async (obj, esPresupuesto) => {
            let qOt = {};
            if (esPresupuesto) {
                qOt = await presupuestoSrvc.lstOts(+obj.id);
                qOt = procDataOts(qOt)[0];
                //console.log(qOt);
            }
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalDetPagosOt.html',
                controller: 'ModalDetPagosOtCtrl',
                resolve: {
                    ot: () => (esPresupuesto ? qOt : obj),
                    permiso: () => $scope.permiso
                }
            });
            modalInstance.result.then((obj) => { }, () => { });
        };

        $scope.adherirAOTM = async (obj, esPresupuesto) => {
            let qOt = {};
            if (esPresupuesto) {
                qOt = await presupuestoSrvc.lstOts(+obj.id);
                qOt = procDataOts(qOt)[0];
                // console.log(qOt);
            }

            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAttachTo.html',
                controller: 'ModalAttachToCtrl',
                resolve: {
                    ot: () => (esPresupuesto ? qOt : obj),
                    permiso: () => $scope.permiso
                }
            });
            modalInstance.result.then((d) => {
                toaster.pop('info', 'Adhesión de OT a OTM', `El nuevo número de la OT es ${d.numero}...`, 'timeout:1500');
                $scope.getLstPresupuestos('1,2,3');
                if (esPresupuesto) {
                    $scope.getPresupuesto(obj.id, true);
                } else {
                    $scope.getLstOts(obj.idpresupuesto);
                    $scope.getOt(obj.id);
                }
            }, () => { });
        };

        $scope.ampliar = function (obj) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAmpliarOt.html',
                controller: 'ModalAmpliarOtCtrl',
                resolve: {
                    ot: function () { return obj; },
                    permiso: function () { return $scope.permiso; }
                }
            });
            modalInstance.result.then(function (obj) {
                //console.log(obj);
            }, function () { return 0; });
        };

        $scope.aumentaExcedente = function (obj, esot) {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAumentaExcedente.html',
                controller: 'ModalAumentaExcedenteCtrl',
                resolve: {
                    presupuesto: function () { return obj; },
                    usr: function () { return $scope.usrdata; },
                    esot: function () { return esot ? true : false; }
                }
            });
            modalInstance.result.then(function (obj) {
                $scope.getLstPresupuestos('1,2,3');
                if (!obj.esot) {
                    $scope.getPresupuesto(obj.id, true);
                } else {
                    $scope.getLstOts(obj.idpresupuesto);
                    $scope.getOt(obj.id);
                }
            }, function () { return 0; });
        };

        $scope.groupPrint = (nvoformato) => {
            //console.log('NUEVO = ', nvoformato);
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalGroupPrint.html',
                controller: 'ModalGroupPrintCtrl',
                resolve: {
                    nuevoFormato: function() { return nvoformato; }
                }
            });

            modalInstance.result.then(() => { }, () => { });
        };

        $scope.otAdjunto = { };
        $scope.lstotadjuntos = [];

        $scope.loadOTAdjuntos = (id, multiple) => {
            //if($scope.ot.id > 0) {id = $scope.ot.id}
            //else {id = $scope.presupuesto.id};
            //if($scope.ot.id > 0) {multiple = 0}
            //else {multiple = 1};
            if(!id) { id = $scope.ot.id; }
            if(!multiple) { multiple = 0; }
            presupuestoSrvc.lstOtsAdjuntos(id, multiple).then((d) => $scope.lstotadjuntos = d);
        };

        $scope.resetOTAdjunot = () => $scope.otAdjunto = { idot: undefined, ubicacion: undefined, esmultiple: 0 };

        $scope.resetOTAdjunot();

        $scope.upload = () => {
            const file = $scope.file;
            console.log(file);
            if (file){
                Upload.upload({
                    url: 'php/upload.php',
                    method: 'POST',
                    file: file,
                    sendFieldsAs: 'form',
                    fields: {
                        directorio: '../ots_adjunto/',
                        prefijo: 'OT_' + $scope.ot.id + '_'
                    }
                }).then(() => {
                    $scope.file = null;
                    $scope.progressPercentage = 0;
                },
                    () => { },
                    (evt) => $scope.progressPercentage = parseInt(100.0 * evt.loaded / evt.total)
                );
            }
        };        

        $scope.addOTAdjunto = () => {
            $scope.upload();
            if ($scope.ot.id > 0) {
                $scope.otAdjunto.idot = $scope.ot.id
            } else {
                $scope.otAdjunto.idot = $scope.presupuesto.id
            };
            $scope.otAdjunto.ubicacion = "ots_adjunto/"+'OT_'+(($scope.ot && $scope.ot.id) ? $scope.ot.id : (`${$scope.presupuesto.id}_1`))+'_'+ $filter('textCleaner')($scope.file.name);
            if ($scope.ot.id > 0) {
                $scope.otAdjunto.esmultiple = 0
            } else {
                $scope.otAdjunto.esmultiple = 1
            };
            presupuestoSrvc.editRow($scope.otAdjunto, 'aaot').then(() => {                
                if($scope.ot && $scope.ot.id){
                    $scope.loadOTAdjuntos();
                } else {
                    $scope.loadOTAdjuntos($scope.presupuesto.id, 1);
                }
            });
        };

        $scope.delOTAdjunto = (id) => {
            $confirm({
                text: '¿Seguro(a) de eliminar este adjunto? (Esto también eliminará físicamente el documento)',
                title: 'Eliminar adjunto de OT', ok: 'Sí', cancel: 'No'}).then(() => presupuestoSrvc.editRow({ id: id }, 'daot').then(() => {
                    if($scope.ot && $scope.ot.id){
                        $scope.loadOTAdjuntos();
                    } else {
                        $scope.loadOTAdjuntos($scope.presupuesto.id, 1);
                    }
                }));
        };

        // $scope.setTC = () => $scope.presupuesto.tipocambio = $scope.tipocambiogt;        
    }]);

        //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        presupuestoctrl.controller('ModalAttachToCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', 'permiso', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot, permiso) {
            $scope.ot = ot;
            $scope.otms = [];
            $scope.params = {
                id: $scope.ot.id, idpresupuesto: undefined
            };            

            $scope.loadListaOTMs = () => { presupuestoSrvc.lstOTMs().then((d) => $scope.otms = d); };

            $scope.ok = function () {                
                $confirm({ text: '¿Esta seguro(a) de continuar?', title: 'Adherir OT a OTM', ok: 'Sí', cancel: 'No' }).then(() => {
                    presupuestoSrvc.editRow($scope.params, 'attachto').then((d) => { $uibModalInstance.close(d); });
                });
            };

            $scope.cancel = () => $uibModalInstance.dismiss('cancel');

            $scope.loadListaOTMs();
        }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalDetPagosOtCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', 'permiso', 'monedaSrvc', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot, permiso, monedaSrvc) {
        $scope.ot = ot;
        $scope.lstdetpagos = [];
        $scope.fpago = { iddetpresup: ot.id, isr: 0.00, quitarisr: 0 };
        $scope.sumporcentaje = 0.0000;
        $scope.sumvalor = 0.00;
        $scope.permiso = permiso;
        $scope.valorexcede = parseFloat(parseFloat(parseFloat($scope.ot.monto) * (1 + parseFloat($scope.ot.excedente) / 100)).toFixed(2));
        $scope.porexcede = parseFloat(parseFloat(100.00 + parseFloat($scope.ot.excedente)).toFixed(2));
        $scope.monedas = [];

        monedaSrvc.lstMonedas().then(function (d) { $scope.monedas = d; });

        function procDataDet(d) {
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

        $scope.loadData = () => presupuestoSrvc.lstDetPagoOt($scope.ot.id).then((d) => $scope.lstdetpagos = procDataDet(d));

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

        $scope.loadData();

        $scope.calculaISR = function () {
            presupuestoSrvc.editRow({ idot: $scope.ot.id, monto: $scope.fpago.monto }, 'calcisr').then(function (d) {
                $scope.fpago.isr = d.isr;
            });
        };

        $scope.calcValor = function () {
            var tmpVal = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
            if (($scope.sumvalor + tmpVal) <= $scope.valorexcede) {
                $scope.fpago.monto = tmpVal;
                // $scope.calculaISR(); // Se quita temporalmente este cambio 04/04/2019
            } else {
                toaster.pop('error', 'Error en el monto', 'La suma de las formas de pago no puede exceder al total de la OT', 'timeout:1500');
                $scope.loadData();
            }
        };

        $scope.calcPorcentaje = function () {
            var tmpPor = parseFloat(parseFloat(parseFloat($scope.fpago.monto) * 100.0000 / parseFloat($scope.ot.monto)).toFixed(4));
            if (($scope.sumporcentaje + tmpPor) <= $scope.porexcede) {
                $scope.fpago.porcentaje = tmpPor;
                // $scope.calculaISR(); // Se quita temporalmente este cambio 04/04/2019
            } else {
                toaster.pop('error', 'Error en el porcentaje', 'La suma porcentual no puede ser mayor a 100.00%', 'timeout:1500');
                $scope.loadData();
            }
        };

        $scope.resetFPago = function () {
            $scope.fpago = { iddetpresup: ot.id, quitarisr: 0, isr: 0.00 }
        };

        $scope.getFormaPago = (obj) => {
            presupuestoSrvc.getDetPagoOt(obj.id).then((d) => $scope.fpago = procDataDet(d)[0]);
        }

        $scope.addFormaPago = function (obj) {
            obj.isr = obj.isr !== undefined && obj.isr != null ? obj.isr : 0;
            obj.quitarisr = obj.quitarisr !== undefined && obj.quitarisr != null ? obj.quitarisr : 0;
            obj.notas = obj.notas !== undefined && obj.notas != null ? obj.notas : '';
            presupuestoSrvc.editRow(obj, 'cdp').then(function () {
                $scope.loadData();
                $scope.resetFPago();
            });
        };

        $scope.updFormaPago = (obj) => {
            obj.isr = obj.isr !== undefined && obj.isr != null ? obj.isr : 0;
            obj.quitarisr = obj.quitarisr !== undefined && obj.quitarisr != null ? obj.quitarisr : 0;
            obj.notas = obj.notas !== undefined && obj.notas != null ? obj.notas : '';
            presupuestoSrvc.editRow(obj, 'udp').then(function () {
                $scope.loadData();
                $scope.resetFPago();
            });
        };

        $scope.delFormaPago = function (obj) {
            $confirm({ text: '¿Esta seguro(a) de eliminar la forma de pago No. ' + obj.nopago + '?', title: 'Eliminar forma de pago', ok: 'Sí', cancel: 'No' }).then(function () {
                presupuestoSrvc.editRow({ id: obj.id }, 'ddp').then(function () { $scope.loadData(); $scope.resetFPago(); });
            });
        };

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalAnulaPresupuesto', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'razonAnulacionSrvc', 'presupuesto', 'usr', 'idpresupuesto', 'correlativo', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, razonAnulacionSrvc, presupuesto, usr, idpresupuesto, correlativo) {
        $scope.presupuesto = presupuesto;
        $scope.razones = [];
        $scope.usr = usr;
        $scope.params = { id: $scope.presupuesto.id, idusuarioanula: $scope.usr.uid, idrazonanula: undefined, esot: 0 };

        $scope.numpresup = $scope.presupuesto.id;
        $scope.params.esot = 0;
        if (idpresupuesto && correlativo) {
            $scope.numpresup = `${idpresupuesto}-${correlativo}`;
            $scope.params.esot = 1;
        }

        razonAnulacionSrvc.lstRazones().then(function (d) { $scope.razones = d; });

        $scope.ok = function () {
            $confirm({ text: `¿Esta seguro(a) de anular la OT No. ${$scope.numpresup}?`, title: 'Anular OT', ok: 'Sí', cancel: 'No' }).then(function () {
                presupuestoSrvc.editRow($scope.params, 'anulapres').then(function () { $uibModalInstance.close(); });
            },
                function () { $scope.cancel(); }
            );
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalAumentaExcedenteCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'presupuesto', 'usr', 'esot', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, presupuesto, usr, esot) {
        $scope.presupuesto = presupuesto;
        $scope.usr = usr;
        $scope.esot = esot;
        $scope.params = { id: $scope.presupuesto.id, idusuarioaumentaexcedente: $scope.usr.uid, monto: parseFloat($scope.presupuesto.excedente), esot: esot ? 1 : 0 };

        $scope.ok = function () {
            //console.log($scope.params);
            presupuestoSrvc.editRow($scope.params, 'masexcede').then(function () { $uibModalInstance.close({ id: $scope.params.id, esot: $scope.esot, idpresupuesto: $scope.presupuesto.idpresupuesto || null }); });
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalAmpliarOtCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', 'permiso', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot, permiso) {
        $scope.ot = ot;
        $scope.lstampliaciones = [];
        $scope.amplia = { idpresupuesto: ot.idpresupuesto, iddetpresupuesto: ot.id, idmoneda: ot.idmoneda, tipocambio: ot.tipocambio };
        $scope.permiso = permiso;
        $scope.sumaAmpliaciones = 0.00;

        function procDataDet(d) {
            $scope.sumaAmpliaciones = 0.00;
            for (var i = 0; i < d.length; i++) {
                //id, idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas
                d[i].id = +d[i].id;
                d[i].idpresupuesto = +d[i].idpresupuesto;
                d[i].iddetpresupuesto = +d[i].iddetpresupuesto;
                d[i].correlativoamplia = +d[i].correlativoamplia;
                d[i].monto = parseFloat(d[i].monto);
                $scope.sumaAmpliaciones += d[i].monto;
            }
            return d;
        }

        $scope.loadData = function () {
            presupuestoSrvc.lstAmpliaciones($scope.ot.id).then(function (d) {
                $scope.lstampliaciones = procDataDet(d);
            });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.resetAmpliacion = function () {
            $scope.amplia = { idpresupuesto: ot.idpresupuesto, iddetpresupuesto: ot.id, idmoneda: ot.idmoneda, tipocambio: ot.tipocambio };
        };

        $scope.addAmpliacion = function (obj) {
            obj.notas = obj.notas !== undefined && obj.notas != null ? obj.notas : '';
            presupuestoSrvc.editRow(obj, 'cap').then(function () {
                $scope.loadData();
                $scope.resetAmpliacion();
            });
        };

        $scope.enviarRevision = function (obj) {
            $confirm({ text: '¿Esta seguro(a) de enviar a revisión la ampliación No. ' + obj.correlativoamplia + '?', title: 'Enviar a revisión', ok: 'Sí', cancel: 'No'}).then(function () {
                presupuestoSrvc.editRow({ idamplia: obj.id }, 'revap').then(function () { $scope.loadData(); $scope.resetAmpliacion(); });
            });
        };

        $scope.delAmpliacion = function (obj) {
            $confirm({ text: '¿Esta seguro(a) de eliminar la ampliación No. ' + obj.correlativoamplia + '?', title: 'Eliminar ampliación', ok: 'Sí', cancel: 'No' }).then(function () {
                presupuestoSrvc.editRow({ idamplia: obj.id }, 'dap').then(function () { $scope.loadData(); $scope.resetAmpliacion(); });
            });
        };

        $scope.loadData();
        console.log(ot);

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalGroupPrintCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', '$http', '$window', '$q', 'nuevoFormato', function ($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, $http, $window, $q, nuevoFormato) {
        $scope.params = { fecha: moment().toDate() };
        $scope.content = undefined;

        $scope.ok = () => {
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            presupuestoSrvc.lstOtsImprimir($scope.params).then(generados => {
                const url = window.location.origin + ':5489/api/report';
                let props = {}, file, formData = new FormData();
                //console.log('NUEVO (MODAL) = ', nuevoFormato);
                const shortId = nuevoFormato ? 'rJPo84G0w' : 'S1eAuyN2b';

                const promises = generados.map(generado => {
                    props = { 'template': { 'shortid': shortId }, 'data': { idot: generado.idot } };
                    return $http.post(url, props, { responseType: 'arraybuffer' });
                });

                $q.all(promises).then((respuestas) => {
                    for (let i = 0; i < generados.length; i++) {
                        file = new Blob([respuestas[i].data], { type: 'application/pdf' });
                        formData.append(`OT_${+generados[i].idot}`, file);
                    }

                    $.ajax({
                        url: "php/rptotgroup.php",
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: () => { },
                        error: () => console.log("Se produjo un error al generar la impresión de OTs...")
                    }).done(() => {
                        const urlpdf = window.location.origin + '/sayet/php/pdfgenerator/OTs.pdf';
                        $window.open(urlpdf);
                        $uibModalInstance.close();
                    });
                });
            });
        };

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');

    }]);


}());
