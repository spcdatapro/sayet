(function(){

    var transegpresupctrl = angular.module('cpm.transegpresupctrl', []);

    transegpresupctrl.controller('tranSegPresupCtrl', ['$scope', 'presupuestoSrvc', '$confirm', '$filter', 'authSrvc', 'DTOptionsBuilder', '$uibModal', 'toaster', function($scope, presupuestoSrvc, $confirm, $filter, authSrvc, DTOptionsBuilder, $uibModal, toaster){

        $scope.ots = [];
        $scope.usrdata = {};
        $scope.expanding_Property = "";
        $scope.col_defs = [];
        $scope.my_tree = $scope.ots = {};
        $scope.fltrot = { fdel: moment('2017-10-01').toDate(), fal: moment().endOf('month').toDate() };

        $scope.dtOptions = DTOptionsBuilder.newOptions().withBootstrap().withOption('paging', false).withOption('order', false);

        authSrvc.getSession().then(function(usrLogged){ $scope.usrdata = usrLogged; });

        function procData(data){
            for(var i = 0; i < data.length; i++){
                data[i].id = parseInt(data[i].id);
                data[i].idproyecto = parseInt(data[i].idproyecto);
                data[i].idproveedor = parseInt(data[i].idproveedor);
                data[i].idpresupuesto = parseInt(data[i].idpresupuesto);
                data[i].idmoneda = parseInt(data[i].idmoneda);
                data[i].correlativo = parseInt(data[i].correlativo);
                data[i].monto = parseFloat(parseFloat(data[i].monto).toFixed(2));
                data[i].fhaprobacion = moment(data[i].fhaprobacion).toDate();
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed(4));
                data[i].excedente = parseFloat(parseFloat(data[i].excedente).toFixed(2));
            }
            return data;
        }

        function setData(d){
            for(var i = 0; i < d.length; i++){
                d[i].proyecto = $filter('shortenStr')(d[i].proyecto, 30);
                for(var j = 0; j < d[i].children.length; j++){
                    d[i].children[j].proyecto = $filter('shortenStr')(d[i].children[j].proyecto, 30);
                    d[i].children[j].tipogasto = $filter('shortenStr')(d[i].children[j].tipogasto, 30);
                }
            }
            return d;
        }

        $scope.getAprobados = function(){
            $scope.fltrot.fdelstr = moment($scope.fltrot.fdel).format('YYYY-MM-DD');
            $scope.fltrot.falstr = moment($scope.fltrot.fal).format('YYYY-MM-DD');
            presupuestoSrvc.presupuestosAprobados($scope.fltrot).then(function(d){
                $scope.ots = setData(d);
            });
        };

        $scope.verSegOt = function(obj){
            //console.log(obj);
            if(obj.children.length == 0){
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalSeguimientoOt.html',
                    controller: 'ModalSegOtCtrl',
                    windowClass: 'app-modal-window',
                    resolve:{
                        ot: function(){ return obj.idot; },
                        usr: function() { return $scope.usrdata; }
                    }
                });

                modalInstance.result.then(function(){
                    console.log('Modal cerrada')
                }, function(){ return 0; });
            }
        };

        $scope.getAprobados();

        $scope.clicked = function(item){ console.log(item); };

        $scope.expanding_Property = {
            field:"id", displayName: "No.", sortable: false, filterable: true
        };

        /*
        $scope.col_defs = [
            {field: "proyecto", displayName: "Proyecto", sortable: false, filterable: true},
            {field: "empresa", displayName: "Empresa", sortable: false, filterable: true},
            {field: "tipogasto", displayName: "Tipo de gasto", sortable: false, filterable: true},
            {field: "moneda", displayName: "Moneda", sortable: false, filterable: true},
            {field: "total", displayName: "Total", sortable: false, filterable: true},
            {field: "descripcion", displayName: "Descripción", sortable: false, filterable: true}
        ];
        */

        $scope.col_defs = [
            {field: "proyecto", displayName: "Proyecto", sortable: false, filterable: true},
            {field: "tipogasto", displayName: "Tipo de gasto", sortable: false, filterable: true},
            {field: "moneda", displayName: "Moneda", sortable: false, filterable: true},
            {field: "total", displayName: "Total", sortable: false, filterable: true, cellTemplate: "<div style='text-align: right; width: 100%'>{{row.branch[col.field]}}</div>"}
        ];
    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    transegpresupctrl.controller('ModalSegOtCtrl', ['$scope', '$uibModalInstance', 'toaster', 'ot', 'usr', 'presupuestoSrvc', '$uibModal', 'jsReportSrvc', '$window', function($scope, $uibModalInstance, toaster, ot, usr, presupuestoSrvc, $uibModal, jsReportSrvc, $window){
        $scope.ot = {};
        $scope.notas = [];
        $scope.nota = {};
        $scope.verFrmNotas = false;
        $scope.avances = [];

        presupuestoSrvc.getOt(+ot).then(function(d){
            $scope.ot = d[0];
            $scope.nota = {iddetpresupuesto: $scope.ot.id, nota: '', idusuario: usr.uid};
            $scope.getNotasOt();
            $scope.getAvance();
        });

        function procDataNotas(data){
            for(var i = 0; i < data.length; i++){
                data[i].fechahora = moment(data[i].fechahora).toDate();
            }
            return data;
        }

        $scope.getNotasOt = function(){
            presupuestoSrvc.notasPresupuesto($scope.ot.id).then(function(d){
                $scope.notas = procDataNotas(d);
            });
        };

        $scope.getAvance = function(){
            presupuestoSrvc.getAvanceOt($scope.ot.id).then(function(d){
                $scope.avances = d;
            });
        };

        //$scope.ok = function () { $uibModalInstance.close($scope.fcierre); };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

        $scope.verFrmNota = function(){ $scope.verFrmNotas = true; };

        $scope.addNota = function(obj){
            presupuestoSrvc.editRow(obj, 'cnp').then(function(){
                $scope.getNotasOt();
                $scope.verFrmNotas = false;
                $scope.nota = {iddetpresupuesto: $scope.ot.id, nota: '', idusuario: usr.uid};
            });
        };

        $scope.cancelAddNote = function(){
            $scope.verFrmNotas = false;
            $scope.nota = {iddetpresupuesto: $scope.ot.id, nota: '', idusuario: usr.uid};
        };

        $scope.shortNote = function(){
            return function(item){
                return item.substring(0, 25) + '...';
            };
        };

        $scope.verDetPagos = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalSegDetPagosOt.html',
                controller: 'ModalSegDetPagosOtCtrl',
                resolve:{ ot: function(){ return obj; } }
            });
            modalInstance.result.then(function(obj){
                //console.log(obj);
            }, function(){ return 0; });
        };

        $scope.printOt = function(idot){
            var test = false;
            //r1cGFmmhZ
            //jsReportSrvc.getPDFReport(test ? 'BJdOgyV2W' : 'S1eAuyN2b', {idot: idot}).then(function(pdf){ $window.open(pdf); });
            jsReportSrvc.getPDFReport(test ? 'r1cGFmmhZ' : 'r1cGFmmhZ', {
                idpresupuesto: +$scope.ot.idpresupuesto,
                idot: idot,
                detallado: 1
            }).then(function(pdf){ $window.open(pdf); });
        };

    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    transegpresupctrl.controller('ModalSegDetPagosOtCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', '$confirm', 'presupuestoSrvc', 'ot', function($scope, $uibModalInstance, $filter, toaster, $confirm, presupuestoSrvc, ot){
        $scope.ot = ot;
        $scope.lstdetpagos = [];
        $scope.fpago = { iddetpresup: ot.id };
        $scope.sumporcentaje = 0.0000;
        $scope.sumvalor = 0.00;
        $scope.valorexcede = parseFloat(parseFloat(parseFloat($scope.ot.monto) * ( 1 + parseFloat($scope.ot.excedente) / 100)).toFixed(2));
        $scope.porexcede = parseFloat(parseFloat(100.00 + parseFloat($scope.ot.excedente)).toFixed(2));

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

            //$scope.fpago.porcentaje = d.length > 0 ? (100 - $scope.sumporcentaje) : 100;
            if($scope.sumporcentaje <= 100){
                $scope.fpago.porcentaje = d.length > 0 ? (100 - $scope.sumporcentaje) : 100;
                $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
            }else{
                $scope.fpago.porcentaje = d.length > 0 ? ($scope.porexcede - $scope.sumporcentaje) : $scope.porexcede;
                $scope.fpago.monto = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.valorexcede) / $scope.porexcede).toFixed(2));
            }

            return d;
        }

        $scope.loadData = function(){
            //console.log($scope.ot); console.log($scope.valorexcede); console.log($scope.porexcede);
            presupuestoSrvc.lstDetPagoOt($scope.ot.id).then(function(d){ $scope.lstdetpagos = procDataDet(d); });
        };

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.loadData();

        $scope.calcValor = function(){
            var tmpVal = parseFloat(parseFloat($scope.fpago.porcentaje * parseFloat($scope.ot.monto) / 100.0000).toFixed(2));
            if( ($scope.sumvalor + tmpVal) <= $scope.valorexcede ){
                $scope.fpago.monto = tmpVal;
            }else{
                $confirm({text: 'La suma de las formas de pago no puede exceder de ' + $filter('number')($scope.valorexcede, 2), title: 'Error en el monto', ok: 'Sí', cancel: 'No'}).then(function() {

                    presupuestoSrvc.editRow({id: obj.id}, 'ddp').then(function(){ $scope.loadData(); $scope.resetFPago(); });

                });

                toaster.pop('error', 'Error en el monto', 'La suma de las formas de pago no puede exceder de ' + $filter('number')($scope.valorexcede, 2), 'timeout:1500');
                $scope.loadData();
            }

        };

        $scope.calcPorcentaje = function(){
            var tmpPor = parseFloat(parseFloat(parseFloat($scope.fpago.monto) * 100.0000 / parseFloat($scope.ot.monto)).toFixed(4));
            if(($scope.sumporcentaje + tmpPor) <= $scope.porexcede){
                $scope.fpago.porcentaje = tmpPor;
            }else{
                toaster.pop('error', 'Error en el porcentaje', 'La suma porcentual no puede ser mayor a ' + $filter('number')($scope.porexcede, 2) + '%', 'timeout:1500');
                $scope.loadData();
            }
        };

        $scope.resetFPago = function(){
            $scope.fpago = { iddetpresup: ot.id }
        };

        $scope.addFormaPago = function(obj){
            obj.notas = obj.notas != undefined && obj.notas != null ? obj.notas : '';
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

}());

