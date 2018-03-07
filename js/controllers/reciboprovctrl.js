(function(){

    var reciboprovctrl = angular.module('cpm.reciboprovctrl', []);

    reciboprovctrl.controller('reciboProveedoresCtrl', ['$scope', 'reciboProveedoresSrvc', 'authSrvc', '$route', '$confirm', '$filter', 'DTOptionsBuilder', 'detContSrvc', 'cuentacSrvc', function($scope, reciboProveedoresSrvc, authSrvc, $route, $confirm, $filter, DTOptionsBuilder, detContSrvc, cuentacSrvc){

        $scope.recprov = {idempresa: 0};
        $scope.recibosprovs = [];
        $scope.permiso = {};
        $scope.tranban = [];
        $scope.detrecprov = {};
        $scope.lstdetrecprov = [];
        $scope.lstdocspend = [];
        $scope.lstdetcont = [];
        $scope.elDetCont = {};
        $scope.origen = 7;
        $scope.cuentas = [];

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('fnRowCallback', rowCallback);

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.recprov.idempresa = parseInt(usrLogged.workingon);
                $scope.getLstRecibosProv($scope.recprov.idempresa);
                $scope.resetRecProv();
                $scope.loadTranBan($scope.recprov.idempresa);
            }
        });

        $scope.resetRecProv = function(){
            $scope.recprov = {
                idempresa: $scope.recprov.idempresa,
                fecha: moment().toDate(),
                idtranban: 0,
                objTranBan: []
            };
            goTop();
        };

        $scope.loadTranBan = function(idempresa){
            reciboProveedoresSrvc.lstTranBan(idempresa).then(function(d){
                for(var i = 0; i< d.length; i++){
                    d[i].id = parseInt(d[i].id);
                    //d[i].numero = parseInt(d[i].numero);
                    //d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                    d[i].fecha = moment(d[i].fecha).toDate();
                }
                $scope.tranban = d;
            });
        };

        $scope.filtrar = function(obj){
            if(!$scope.query ||
                (obj.nombre.toLowerCase().indexOf($scope.query) != -1) ||
                (obj.tipotrans.toLowerCase().indexOf($scope.query) != -1) ||
                (obj.numero.toLowerCase().indexOf($scope.query) != -1) ||
                (obj.simbolo.toLowerCase().indexOf($scope.query) != -1) ||
                (moment(obj.fecha).format('DD/MM/YYYY').indexOf($scope.query) != -1)
            ) {
                return true;
            }
            return false;
        };

        function procDataRecs(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idtranban = parseInt(d[i].idtranban);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].fecha = moment(d[i].fecha).toDate();
            }
            return d;
        }

        $scope.getLstRecibosProv = function(idempresa){
            reciboProveedoresSrvc.lstRecibosProvs(idempresa).then(function(d){
                $scope.recibosprovs = procDataRecs(d) ;
            });
        };

        function procDetCont(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idcuenta = parseInt(d[i].idcuenta);
                d[i].origen = parseInt(d[i].origen);
                d[i].idorigen = parseInt(d[i].idorigen);
                d[i].debe = parseFloat(parseFloat(d[i].debe).toFixed(2));
                d[i].haber = parseFloat(parseFloat(d[i].haber).toFixed(2));
            }
            return d;
        }

        $scope.loadDetCont = function(idrecprov){
            $scope.lstdetcont = [];
            detContSrvc.lstDetalleCont($scope.origen, idrecprov).then(function(d){
                $scope.lstdetcont = procDetCont(d);
            });
        };

        $scope.getRecProv = function(idrecprov){
            reciboProveedoresSrvc.getReciboProv(idrecprov).then(function(d){
                $scope.recprov = procDataRecs(d)[0];
                $scope.recprov.objTranBan = [$filter('getById')($scope.tranban, $scope.recprov.idtranban)];
                $scope.resetDetRecProv();
                $scope.loadDetRecProv(idrecprov);
                $scope.loadDocsPend($scope.recprov.idempresa);
                cuentacSrvc.getByTipo($scope.recprov.idempresa, 0).then(function(d){ $scope.cuentas = d; });
                $scope.loadDetCont(idrecprov);
                goTop();
            });
        };

        function setRecProvData(obj){
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.idtranban = obj.objTranBan[0] != null && obj.objTranBan[0] != undefined ? obj.objTranBan[0].id : 0;
            return obj;
        }

        $scope.addRecProv = function(obj){
            obj = setRecProvData(obj);
            //console.log(obj); return;
            reciboProveedoresSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstRecibosProv(obj.idempresa);
                $scope.getRecProv(parseInt(d.lastid));
            });
        };

        $scope.updRecProv = function(obj){
            obj = setRecProvData(obj);
            //console.log(obj); return;
            reciboProveedoresSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstRecibosProv(obj.idempresa);
                $scope.getRecProv(parseInt(obj.id));
            });
        };

        $scope.delRecProv = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el recibo de proveedores No. ' + $filter('padNumber')(obj.id, 5) + '?', title: 'Eliminar recibo de proveedores', ok: 'Sí', cancel: 'No'}).then(function() {
                reciboProveedoresSrvc.editRow({id: obj.id}, 'd').then(function(){ $scope.getLstRecibosProv(obj.idempresa); $scope.resetRecProv(); });
            });
        };

        $scope.resetDetRecProv = function(){
            $scope.detrecprov = {
                idrecprov: $scope.recprov.id > 0 ? $scope.recprov.id : 0,
                origen: 0,
                idorigen: 0,
                arebajar: 0.00,
                objDocPend: []
            };
            $scope.fltrCompReem = '';
            goTop();
        };

        $scope.loadDocsPend = function(idempresa){
            reciboProveedoresSrvc.lstDocsPend(idempresa).then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].origen = parseInt(d[i].origen);
                    d[i].idorigen = parseInt(d[i].idorigen);
                }
                $scope.lstdocspend = d;
            });
        };

        function procDetaDetRec(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idrecprov = parseInt(d[i].idrecprov);
                d[i].origen = parseInt(d[i].origen);
                d[i].idorigen = parseInt(d[i].idorigen);
                d[i].arebajar = parseFloat(parseFloat(d[i].arebajar).toFixed(2));
            }
            return d;
        }

        $scope.loadDetRecProv = function(idrecprov){
            reciboProveedoresSrvc.getDetRecProv(idrecprov).then(function(d){
                $scope.lstdetrecprov = procDetaDetRec(d);
            });
        };

        $scope.setMontoSugerido = function(){
            if($scope.detrecprov.objDocPend != null && $scope.detrecprov.objDocPend != undefined){
                $scope.detrecprov.arebajar = $scope.detrecprov.objDocPend[0] != null && $scope.detrecprov.objDocPend[0] != undefined ? $scope.detrecprov.objDocPend[0].saldo : 0.00;
            }else{
                $scope.detrecprov.arebajar = 0.00;
            }
        };

        function setDetRec(obj){
            obj.idrecprov = $scope.recprov.id;
            obj.origen = obj.objDocPend[0].origen;
            obj.idorigen = obj.objDocPend[0].idorigen;
            return obj;
        }

        $scope.addDetRecProv = function(obj){
            obj = setDetRec(obj);
            //console.log(obj);
            reciboProveedoresSrvc.editRow(obj, 'cd').then(function(d){
                $scope.loadDetRecProv(obj.idrecprov);
                $scope.loadDocsPend($scope.recprov.idempresa);
                $scope.resetDetRecProv();
            });
        };

        $scope.delDetRecProv = function(obj){
            //console.log(obj); return;
            $confirm({text: '¿Seguro(a) de eliminar ' + obj.cadena + '? (Esto dejará como pendiente el documento)', title: 'Eliminar documento rebajado', ok: 'Sí', cancel: 'No'}).then(function() {
                reciboProveedoresSrvc.editRow({id: obj.id}, 'dd').then(function(){ $scope.loadDetRecProv(obj.idrecprov); $scope.resetDetRecProv(); });
            });
        };

        $scope.zeroDebe = function(valor){ $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function(valor){ $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        $scope.addDetCont = function(obj){
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.recprov.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta[0].id);
            detContSrvc.editRow(obj, 'c').then(function(){
                detContSrvc.lstDetalleCont($scope.origen, $scope.recprov.id).then(function(detc){
                    $scope.lstdetcont = procDetCont(detc);
                    $scope.elDetCont = {debe: 0.0, haber: 0.0};
                    $scope.searchcta = "";
                });
            });
        };

        $scope.delDetCont = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.loadDetCont(obj.idorigen); });
            });
        };

    }]);

}());
