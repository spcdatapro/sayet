(function(){

    var trandirectactrl = angular.module('cpm.trandirectactrl', []);

    trandirectactrl.controller('tranDirectaCtrl', ['$scope', 'directaSrvc', 'authSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'toaster', '$confirm', 'cuentacSrvc', 'detContSrvc', '$window', 'jsReportSrvc', '$uibModal', function($scope, directaSrvc, authSrvc, empresaSrvc, DTOptionsBuilder, toaster, $confirm, cuentacSrvc, detContSrvc, $window, jsReportSrvc, $uibModal){

        $scope.objEmpresa = {};
        $scope.laDirecta = {};
        $scope.lasDirectas = [];
        $scope.editando = false;
        $scope.losDetCont = [];
        $scope.elDetCont = {debe: 0.0, haber: 0.0};
        $scope.lasCtasMov = [];
        $scope.origen = 4;
        $scope.directastr = '';

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('fnRowCallback', rowCallback);

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.laDirecta = {idempresa: parseInt($scope.objEmpresa.id), fecha: new Date()};
                    $scope.getLstDirectas(parseInt($scope.objEmpresa.id));
                });
            }
        });

        function procDataDirectas(data){
            for(var i = 0; i < data.length; i++){
                data[i].id = parseInt(data[i].id);
                data[i].idempresa = parseInt(data[i].idempresa);
                data[i].fecha = moment(data[i].fecha).toDate();
            }
            return data;
        }
        
        $scope.getLstDirectas = function(idempresa){
            directaSrvc.lstDirectas(idempresa).then(function(d){
                $scope.lasDirectas = procDataDirectas(d);
            });
        };

        $scope.resetDirecta = function(){
            $scope.laDirecta = { idempresa: parseInt($scope.objEmpresa.id), fecha: new Date(), concepto: null };
            $scope.editando = false;
            $scope.directastr = '';
        };

        $scope.getDetCont = function(iddirecta){
            detContSrvc.lstDetalleCont($scope.origen, parseInt(iddirecta)).then(function(detc){
                $scope.losDetCont = procDataDet(detc);
                goTop();
            });
        };

        $scope.getPartidaDirecta = function(iddirecta){
            directaSrvc.getDirecta(iddirecta).then(function(d){
                $scope.laDirecta = procDataDirectas(d)[0];
                $scope.editando = true;
                $scope.directastr = 'partida directa con correlativo No. ' + $scope.laDirecta.id + ', de fecha: ' + moment($scope.laDirecta.fecha).format('DD/MM/YYYY');
                cuentacSrvc.getByTipo($scope.objEmpresa.id, 0).then(function(d){ $scope.lasCtasMov = d; });
                $scope.getDetCont(iddirecta);
            });
        };

        $scope.addDirecta = function(obj){
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.concepto = obj.concepto != null && obj.concepto != undefined ? obj.concepto : '';
            directaSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstDirectas($scope.objEmpresa.id);
                $scope.getPartidaDirecta(parseInt(d.lastid));
            });
        };

        $scope.updDirecta = function(obj){
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.concepto = obj.concepto != null && obj.concepto != undefined ? obj.concepto : '';
            directaSrvc.editRow(obj, 'u').then(function(d){
                $scope.getLstDirectas($scope.objEmpresa.id);
                $scope.getPartidaDirecta(obj.id);
            });
        };

        $scope.printDirecta = function(){
            var test = false;
            jsReportSrvc.getPDFReport((test ? 'Hkz4lAnBz' : 'Sk6jO1aBf'), {iddirecta: $scope.laDirecta.id}).then(function(pdf){ $window.open(pdf); });
        };

        $scope.delDirecta = function(iddirecta){
            $confirm({text: '¿Seguro(a) de eliminar esta partida directa?', title: 'Eliminar partida directa', ok: 'Sí', cancel: 'No'}).then(function() {
                directaSrvc.editRow({id:iddirecta}, 'd').then(function(){ $scope.getLstDirectas(parseInt($scope.objEmpresa.id)); });
            });
        };

        $scope.zeroDebe = function(valor){ $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function(valor){ $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        function procDataDet(data){
            for(var i = 0; i < data.length; i++){
                data[i].debe = parseFloat(data[i].debe);
                data[i].haber = parseFloat(data[i].haber);
            }
            return data;
        }

        $scope.addDetCont = function(obj){
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.laDirecta.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta[0].id);
            detContSrvc.editRow(obj, 'c').then(function(){
                detContSrvc.lstDetalleCont($scope.origen, parseInt($scope.laDirecta.id)).then(function(detc){
                    $scope.losDetCont = procDataDet(detc);
                    $scope.elDetCont = {debe: 0.0, haber: 0.0};
                    $scope.searchcta = "";
                });
            });
        };

        $scope.updDetCont = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalUpdDetCont.html',
                controller: 'ModalUpdDetContCtrl',
                resolve:{
                    detalle: function(){ return obj; },
                    idempresa: function(){return +$scope.laDirecta.idempresa; }
                }
            });

            modalInstance.result.then(function(){
                $scope.getDetCont(obj.idorigen);
            }, function(){ $scope.getDetCont(obj.idorigen); });
        };

        $scope.delDetCont = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.getDetCont(obj.idorigen); });
            });
        };

    }]);

    trandirectactrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm){
        $scope.detcont = detalle;
        $scope.cuentas = [];

        cuentacSrvc.getByTipo(idempresa, 0).then(function(d){ $scope.cuentas = d; });

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

        $scope.zeroDebe = function(valor){ $scope.detcont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.debe; };
        $scope.zeroHaber = function(valor){ $scope.detcont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.haber; };

        $scope.actualizar = function(obj){
            $confirm({text: '¿Seguro(a) de guardar los cambios?', title: 'Modificar detalle contable', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow(obj, 'u').then(function(){ $scope.ok(); });
            });
        };

    }]);

}());
