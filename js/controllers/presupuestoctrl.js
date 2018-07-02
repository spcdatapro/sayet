(function(){

    var presupuestoctrl = angular.module('cpm.presupuestoctrl', []);

    presupuestoctrl.controller('presupuestoCtrl', ['$scope', 'presupuestoSrvc', '$confirm', 'proyectoSrvc', 'empresaSrvc', 'tipogastoSrvc', 'monedaSrvc', '$filter', 'authSrvc', 'proveedorSrvc', 'toaster', '$uibModal', 'desktopNotification', 'jsReportSrvc', '$window', 'tranBancSrvc', 'estatusPresupuestoSrvc', '$route', function($scope, presupuestoSrvc, $confirm, proyectoSrvc, empresaSrvc, tipogastoSrvc, monedaSrvc, $filter, authSrvc, proveedorSrvc, toaster, $uibModal, desktopNotification, jsReportSrvc, $window, tranBancSrvc, estatusPresupuestoSrvc, $route){

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
        $scope.sl = {presupuesto: true, ot: true};
        $scope.usrdata = {};
        $scope.permiso = {};
        $scope.lbl = {presupuesto: '', ot: ''};

        $scope.grpBtnPresupuesto = {i: false, p:false, e: false, u: false, c: false, d: false, a: true};
        $scope.grpBtnOt = {i: false, p:false, e: false, u: false, c: false, d: false, a: true};
        $scope.showForm = {presupuesto: false, ot: false};
        $scope.fltrot = { fdel: moment('2017-10-01').toDate(), fal: moment().endOf('month').toDate(), idestatuspresup: null, idusuario: 0 };
        $scope.lstestatuspresup = [];

        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        tipogastoSrvc.lstTipogastos().then(function(d){ $scope.tiposgasto = d; });
        monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });
        //proveedorSrvc.lstProveedores().then(function(d){ $scope.proveedores = d; });
        tranBancSrvc.lstBeneficiarios().then(function(d){ $scope.proveedores = d; });

        authSrvc.getSession().then(function(usrLogged){
            $scope.usrdata = usrLogged;
            $scope.fltrot.idusuario = $scope.usrdata.uid;
            authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
            $scope.getLstPresupuestos('1,2,3');
        });

        estatusPresupuestoSrvc.lstEstatusPresupuesto().then(function(d){ $scope.lstestatuspresup = d; });

        $scope.confGrpBtn = function(grp, i, u, d, a, e, c, p){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c; $scope." + grp + ".p = p;";
            eval(instruccion);
        };

        function procDataPresup(data) {
            for(var i = 0; i < data.length; i++){
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
            }
            return data;
        }

        $scope.getLstPresupuestos = function(idestatuspresup){
            $scope.fltrot.fdelstr = moment($scope.fltrot.fdel).format('YYYY-MM-DD');
            $scope.fltrot.falstr = moment($scope.fltrot.fal).format('YYYY-MM-DD');
            $scope.fltrot.idestatuspresup = idestatuspresup != null && idestatuspresup !== undefined ? idestatuspresup : '';
            presupuestoSrvc.lstPresupuestos($scope.fltrot).then(function(d){
                $scope.lstpresupuestos = procDataPresup(d);
            });
        };

        $scope.resetPresupuesto = function(){
            $scope.presupuesto = {fechasolicitud: moment().toDate(), idmoneda: '1', tipocambio: 1.00};
            $scope.ot = {};
            $scope.lstot = [];
            $scope.srchproy = '';
            $scope.srchemp = '';
            $scope.lbl.presupuesto = '';
            $scope.lbl.ot = '';
        };

        function setPresupuesto(obj){
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
            obj.monto = obj.monto != null && obj.monto != undefined ? obj.monto : 0.00;
            obj.tipocambio = obj.tipocambio != null && obj.tipocambio != undefined ? obj.tipocambio : 1.0000;
            return obj;
        }

        $scope.loadSubtTiposGasto = function(idtipogasto){
            tipogastoSrvc.lstSubTipoGastoByTipoGasto(+idtipogasto).then(function(d){ $scope.subtiposgasto = d; });
        };

        $scope.getPresupuesto = function(idpresupuesto, movertab){
            if(movertab == null || movertab == undefined){ movertab = true }
            $scope.ot = {};
            $scope.lstot = [];
            presupuestoSrvc.getPresupuesto(idpresupuesto).then(function(d){
                $scope.presupuesto = procDataPresup(d)[0];
                $scope.presupuesto.proyecto = $scope.presupuesto.idproyecto;
                $scope.presupuesto.empresa = $scope.presupuesto.idempresa;
                $scope.loadSubtTiposGasto($scope.presupuesto.idtipogasto);
                $scope.getLstOts(idpresupuesto);
                $scope.lbl.presupuesto = 'No. ' + $scope.presupuesto.id + ' - ' + ($filter('getById')($scope.proyectos, $scope.presupuesto.idproyecto)).nomproyecto + ' - ';
                $scope.lbl.presupuesto+= ($filter('getById')($scope.empresas, $scope.presupuesto.idempresa)).nomempresa + ' - ';
                $scope.lbl.presupuesto+= ($filter('getById')($scope.tiposgasto, $scope.presupuesto.idtipogasto)).desctipogast + ' - ';
                $scope.lbl.presupuesto+= ($filter('getById')($scope.monedas, $scope.presupuesto.idmoneda)).simbolo + ' ';
                $scope.lbl.presupuesto+= $filter('number')($scope.presupuesto.total, 2);
                $scope.confGrpBtn('grpBtnPresupuesto', false, false, true, true, true, false, false);
                $scope.sl.presupuesto = true;
                if(movertab){
                    moveToTab('divLstPresup', 'divFrmPresup');
                }
                goTop();
            });
        };

        $scope.cancelEditPresup = function(){
            if($scope.presupuesto.id > 0){
                $scope.getPresupuesto($scope.presupuesto.id);
            }else{
                $scope.resetPresupuesto();
            }
            $scope.confGrpBtn('grpBtnPresupuesto', false, false, false, true, false, false, false);
            $scope.sl.presupuesto = true;
        };

        $scope.startEditPresup = function(){
            $scope.sl.presupuesto = false;
            $scope.confGrpBtn('grpBtnPresupuesto', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirPresup = function(){ console.log('Función pendiente...') };

        $scope.printPrespuesto = function(idpresupuesto, adetalle){
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'r1UD2qMnZ' : 'r1cGFmmhZ', {idpresupuesto: idpresupuesto, detallado: adetalle}).then(function(pdf){ $window.open(pdf); });

        };

        $scope.nuevoPresupuesto = function(){
            $scope.sl.presupuesto = false;
            $scope.resetPresupuesto();
            $scope.confGrpBtn('grpBtnPresupuesto', true, false, false, false, false, true, false);
        };

        $scope.setEmpresa = function(item){
            //console.log(item);
            $scope.presupuesto.empresa = item.idempresa;
        };

        $scope.setOrigenProv = function(item, model){
            $scope.presupuesto.origenprov = +item.dedonde;
        };

        $scope.addPresupuesto = function(obj){
            obj = setPresupuesto(obj);
            presupuestoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto(parseInt(d.lastid));
                $scope.srchproy = '';
                $scope.srchemp = '';
            });
        };

        $scope.updPresupuesto = function(obj){
            obj = setPresupuesto(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'u').then(function(d){
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto(obj.id);
                $scope.srchproy = '';
                $scope.srchemp = '';
            });
        };

        $scope.delPresupuesto = function(obj){
            $confirm({text: '¿Esta seguro(a) de eliminar el presupuesto No. ' + obj.id +'?', title: 'Eliminar presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                presupuestoSrvc.editRow({id: obj.id}, 'd').then(function(){ $scope.getLstPresupuestos('1,2,3'); $scope.resetPresupuesto(); });
            });
        };

        $scope.enviar = function(obj){
            $confirm({text: '¿Esta seguro(a) de enviar el presupuesto No. ' + obj.id +' para aprobación?', title: 'Envio de presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/ep').then(function(){
                    $scope.getLstPresupuestos('1,2,3');
                    toaster.pop('info', 'Envio de presupuesto', 'Presupuesto No. ' + obj.id + ' enviado a aprobación...', 'timeout:1500');
                });
            });
        };

        $scope.terminaPresupuesto = function(obj){
            $confirm({text: '¿Esta seguro(a) de terminar el presupuesto No. ' + obj.id +'? Si lo termina, ya no podrá modificarlo a menos que lo reaperturen.', title: 'Terminar presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/tp').then(function(){
                    $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(obj.id, true);
                    toaster.pop('info', 'Terminar presupuesto', 'Presupuesto No. ' + obj.id + ' terminado...', 'timeout:1500');
                });
            });
        };

        $scope.reabrirPresupuesto = function(obj){
            $confirm({text: '¿Esta seguro(a) de abrir nuevamente el presupuesto No. ' + obj.id +'?', title: 'Re-abrir presupuesto', ok: 'Sí', cancel: 'No'}).then(function() {
                obj.idusuario = $scope.usrdata.uid;
                presupuestoSrvc.editRow(obj, '/rp').then(function(){
                    $scope.getLstPresupuestos('1,2,3');
                    $scope.getPresupuesto(obj.id, true);
                    toaster.pop('info', 'Re-abrir presupuesto', 'Presupuesto No. ' + obj.id + ' reaperturado...', 'timeout:1500');
                });
            });
        };

        $scope.anulaPresupuesto = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAnulaPresupuesto.html',
                controller: 'ModalAnulaPresupuesto',
                resolve:{
                    presupuesto: function(){ return obj; },
                    usr: function(){ return $scope.usrdata; }
                }
            });
            modalInstance.result.then(function(){
                moveToTab('divFrmPresup', 'divLstPresup');
                $scope.resetPresupuesto();
                $scope.confGrpBtn('grpBtnPresupuesto', false, false, false, true, false, false, false);
                $scope.getLstPresupuestos('1,2,3');
            }, function(){ return 0; });
        };

        // $scope.getLstPresupuestos('1,2,3');

        function procDataOts(data){
            for(var i = 0; i < data.length; i++){
                data[i].id = parseInt(data[i].id);
                data[i].idpresupuesto = parseInt(data[i].idpresupuesto);
                data[i].correlativo = parseInt(data[i].correlativo);
                data[i].coniva = parseInt(data[i].coniva);
                data[i].monto = parseFloat(parseFloat(data[i].monto).toFixed(2));
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed(4));
                data[i].excedente = parseFloat(parseFloat(data[i].excedente).toFixed(2));
                data[i].origenprov = parseInt(data[i].origenprov);
            }
            return data;
        }

        $scope.getLstOts = function(idpresupuesto){
            presupuestoSrvc.lstOts(idpresupuesto).then(function(d){
                $scope.lstot = procDataOts(d);
            });
        };

        $scope.getOt = function(idot){
            presupuestoSrvc.getOt(idot).then(function(d){
                $scope.ot = procDataOts(d)[0];
                $scope.confGrpBtn('grpBtnOt', false, false, true, true, true, false, false);
                $scope.sl.ot = true;
                $scope.showForm.ot = true;
                goTop();
            });
        };

        $scope.resetOt = function(){
            $scope.ot = { idpresupuesto: $scope.presupuesto.id, coniva: 1, monto: 0.00, idproveedor: undefined, idsubtipogasto: undefined, tipocambio: 1.0000, origenprov: 0 }
        };

        $scope.cancelEditOt = function(){
            if($scope.ot.id > 0){
                $scope.getOt($scope.ot.id);
            }else{
                $scope.resetOt();
            }
            $scope.confGrpBtn('grpBtnOt', false, false, false, true, false, false, false);
            $scope.sl.ot = true;
        };

        $scope.startEditOt = function(){
            $scope.sl.ot = false;
            $scope.confGrpBtn('grpBtnOt', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirOt = function(){ console.log('Función pendiente...') };

        $scope.nuevaOt = function(){
            $scope.sl.ot = false;
            $scope.resetOt();
            $scope.confGrpBtn('grpBtnOt', true, false, false, false, false, true, false);
        };

        $scope.tryNotify = function(){
            desktopNotification.show('PRUEBA DE NOTIFICACIONES!!!', {
                icon: 'img/sayet.ico',
                body: 'HOLA!!!!',
                onClick: function(){
                    console.log('Clicked on notification...')
                }
            });
        };

        function setDataOt(obj){
            obj.idpresupuesto = $scope.presupuesto.id;
            return obj;
        }

        $scope.setOrigenProvOt = function(item, model){
            $scope.ot.origenprov = +item.dedonde;
        };

        $scope.addOt = function(obj){
            obj = setDataOt(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'cd').then(function(d){
                $scope.getLstPresupuestos('1,2,3');
                $scope.getPresupuesto($scope.presupuesto.id, false);
                $scope.getLstOts($scope.presupuesto.id);
                $scope.getOt(parseInt(d.lastid));
            });
        };

        $scope.updOt = function(obj){
            obj = setDataOt(obj);
            //console.log(obj); return;
            presupuestoSrvc.editRow(obj, 'ud').then(function(d){
                $scope.getLstOts($scope.presupuesto.id);
                $scope.getOt(obj.id);
            });
        };

        $scope.delOt = function(obj){
            $confirm({text: '¿Esta seguro(a) de eliminar la OT No. ' + obj.idpresupuesto + '-' + obj.correlativo +'?', title: 'Eliminar OT', ok: 'Sí', cancel: 'No'}).then(function() {
                presupuestoSrvc.editRow({id: obj.id}, 'dd').then(function(){ $scope.getLstOts($scope.presupuesto.id); $scope.resetOt(); });
            });
        };

        $scope.verDetPagos = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalDetPagosOt.html',
                controller: 'ModalDetPagosOtCtrl',
                resolve:{
                    ot: function(){ return obj; },
                    permiso: function(){ return $scope.permiso; }
                }
            });
            modalInstance.result.then(function(obj){
                //console.log(obj);
            }, function(){ return 0; });
        }

    }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalDetPagosOtCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', 'permiso', function($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot, permiso){
        $scope.ot = ot;
        $scope.lstdetpagos = [];
        $scope.fpago = { iddetpresup: ot.id };
        $scope.sumporcentaje = 0.0000;
        $scope.sumvalor = 0.00;
        $scope.permiso = permiso;

        function procDataDet(d){
            $scope.sumporcentaje = 0.0000;
            $scope.sumvalor = 0.00;
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].iddetpresup = parseInt(d[i].iddetpresup);
                d[i].nopago = parseInt(d[i].nopago);
                d[i].porcentaje = parseFloat(parseFloat(d[i].porcentaje).toFixed(4));
                $scope.sumporcentaje += d[i].porcentaje;
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                $scope.sumvalor += d[i].monto;
            }

            $scope.fpago.porcentaje = d.length > 0 ? (100 - $scope.sumporcentaje) : 100;
            $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));

            return d;
        }

        $scope.loadData = function(){
            presupuestoSrvc.lstDetPagoOt($scope.ot.id).then(function(d){ $scope.lstdetpagos = procDataDet(d); });
        };

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.loadData();

        $scope.calcValor = function(){
            var tmpVal = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
            if( ($scope.sumvalor + tmpVal) <= parseFloat($scope.ot.monto) ){
                $scope.fpago.monto = tmpVal;
            }else{
                toaster.pop('error', 'Error en el monto', 'La suma de las formas de pago no puede exceder al total de la OT', 'timeout:1500');
                $scope.loadData();
            }

        };

        $scope.calcPorcentaje = function(){
            var tmpPor = parseFloat(parseFloat(parseFloat($scope.fpago.monto) * 100.0000 / parseFloat($scope.ot.monto)).toFixed(4));
            if(($scope.sumporcentaje + tmpPor) <= 100){
                $scope.fpago.porcentaje = tmpPor;
            }else{
                toaster.pop('error', 'Error en el porcentaje', 'La suma porcentual no puede ser mayor a 100.00%', 'timeout:1500');
                $scope.loadData();
            }
        };

        $scope.resetFPago = function(){
            $scope.fpago = { iddetpresup: ot.id }
        };

        $scope.addFormaPago = function(obj){
            obj.notas = obj.notas !== undefined && obj.notas != null ? obj.notas : '';
            presupuestoSrvc.editRow(obj, 'cdp').then(function(){
                $scope.loadData();
                $scope.resetFPago();
            });
        };

        $scope.delFormaPago = function(obj){
            $confirm({text: '¿Esta seguro(a) de eliminar la forma de pago No. ' + obj.nopago + '?', title: 'Eliminar forma de pago', ok: 'Sí', cancel: 'No'}).then(function() {
                presupuestoSrvc.editRow({id: obj.id}, 'ddp').then(function(){ $scope.loadData(); $scope.resetFPago(); });
            });
        };

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    presupuestoctrl.controller('ModalAnulaPresupuesto', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'razonAnulacionSrvc', 'presupuesto', 'usr', function($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, razonAnulacionSrvc, presupuesto, usr){
        $scope.presupuesto = presupuesto;
        $scope.razones = [];
        $scope.usr = usr;
        $scope.params = { id: $scope.presupuesto.id, idusuarioanula: $scope.usr.uid, idrazonanula: undefined };

        razonAnulacionSrvc.lstRazones().then(function(d){ $scope.razones = d; });

        $scope.ok = function () {
            $confirm({text: '¿Esta seguro(a) de anular la OT No. ' + presupuesto.id + '?', title: 'Anular OT', ok: 'Sí', cancel: 'No'}).then(function() {
                presupuestoSrvc.editRow($scope.params, 'anulapres').then(function(){ $uibModalInstance.close();});
            },
                function(){ $scope.cancel();}
            );
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);

}());
