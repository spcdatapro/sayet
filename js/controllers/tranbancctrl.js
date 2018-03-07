(function(){

    var tranbancctrl = angular.module('cpm.tranbancctrl', ['cpm.tranbacsrvc']);

    tranbancctrl.controller('tranBancCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'tipoDocSopTBSrvc', 'tipoMovTranBanSrvc', 'periodoContableSrvc', 'toaster', 'detContSrvc', 'cuentacSrvc', '$confirm', '$filter', '$uibModal', 'razonAnulacionSrvc', 'presupuestoSrvc', 'jsReportSrvc', '$window', 'localStorageSrvc', 'proyectoSrvc', function($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder, tipoDocSopTBSrvc, tipoMovTranBanSrvc, periodoContableSrvc, toaster, detContSrvc, cuentacSrvc, $confirm, $filter, $uibModal, razonAnulacionSrvc, presupuestoSrvc, jsReportSrvc, $window, localStorageSrvc, proyectoSrvc){

        $scope.laTran = {fecha: new Date(), concepto: '', anticipo: 0, idbeneficiario: 0, tipocambio: parseFloat('1.00').toFixed($scope.dectc), esnegociable: 0};
        $scope.laEmpresa = {};
        $scope.lasEmpresas = [];
        $scope.losBancos = [];
        $scope.lasTran = [];
        $scope.editando = false;
        $scope.strTran = '';
        $scope.fltrtran = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), idbanco: '0' };
        $scope.losDocsSoporte = [];
        $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
        $scope.losTiposDocTB = [];
        $scope.origen = 1;
        $scope.losDetCont = [];
        $scope.elDetCont = {debe: 0.0, haber: 0.0};
        $scope.origenLiq = 9;
        $scope.liquidacion = [];
        $scope.lasCuentasMov = [];
        $scope.beneficiarios = [];
        $scope.compraspendientes = [];
        $scope.razonesanula = [];
        $scope.dectc = 2;
        $scope.ots = [];
        $scope.compras = [];
        $scope.hayDescuadre = false;
        $scope.uid = 0;
        $scope.proyectos = [];
        $scope.selected = {};
        //$scope.tipotrans = [{value: 'C', text: 'C'}, {value: 'D', text: 'D'}, {value: 'B', text: 'B'}, {value: 'R', text: 'R'}];
        $scope.tipotrans = [];
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

        //Infinite Scroll Magic
        $scope.infiniteScroll = {};
        $scope.infiniteScroll.numToAdd = 20;
        $scope.infiniteScroll.currentItems = 20;

        $scope.resetInfScroll = function() {
            $scope.infiniteScroll.currentItems = $scope.infiniteScroll.numToAdd;
        };
        $scope.addMoreItems = function(){
            $scope.infiniteScroll.currentItems += $scope.infiniteScroll.numToAdd;
        };

        $scope.ctaContSelected = function(item){
            console.log(item);
        };

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.lasEmpresas = d;
        });

        tipoMovTranBanSrvc.lstTiposMovTB().then(function(d){ $scope.tipotrans = d; });
        tranBancSrvc.lstBeneficiarios().then(function(d){ $scope.beneficiarios = d; });
        razonAnulacionSrvc.lstRazones().then(function(d){$scope.razonesanula = d; });

        authSrvc.getSession().then(function(usrLogged){
			$scope.uid = +usrLogged.uid;
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.laEmpresa = d[0];
                    $scope.dectc = parseInt(d[0].dectc);
                    $scope.getLstBancos();
                    presupuestoSrvc.lstPagosOt($scope.laEmpresa.id).then(function(d){ $scope.ots = d; });
                    proyectoSrvc.lstProyectosPorEmpresa($scope.laEmpresa.id).then(function(d){ $scope.proyectos = d; });
                });
            }
        });

        $scope.$watch('laTran.fecha', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.chkFechaEnPeriodo(newValue, 't');
            }
        });

        $scope.$watch('elDocSop.fechadoc', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.chkFechaEnPeriodo(newValue, 'd');
            }
        });

        $scope.getTranInicial = function(){            
            var idtranbanini = localStorageSrvc.get('idtranban');
            if(idtranbanini != null && idtranbanini != undefined){
                localStorageSrvc.clear('idtranban');
                $scope.getDataTran(+idtranbanini);
            }
        };

        $scope.getLstBancos = function(){
            bancoSrvc.lstBancosActivos(parseInt($scope.laEmpresa.id)).then(function(r){
                $scope.losBancos = r;
                $scope.lasTran = [];
                $scope.getTranInicial();
            });
        };

        $scope.getLstTran = function(){
            if($scope.laTran.objBanco != null && $scope.laTran.objBanco != undefined){
                $scope.laTran.tipocambio = parseFloat($scope.laTran.objBanco.tipocambio).toFixed($scope.dectc);
                $scope.cleanInfo();
                $scope.fltrtran.idbanco = $scope.laTran.objBanco.id;
                $scope.fltrtran.fdelstr = moment($scope.fltrtran.fdel).format('YYYY-MM-DD');
                $scope.fltrtran.falstr = moment($scope.fltrtran.fal).format('YYYY-MM-DD');
                tranBancSrvc.lstTranFiltr($scope.fltrtran).then(function(d){
                    $scope.lasTran = d;
                    for(var i = 0; i < $scope.lasTran.length; i++){
                        $scope.lasTran[i].fecha = moment($scope.lasTran[i].fecha).toDate();
                        $scope.lasTran[i].numero = parseInt($scope.lasTran[i].numero);
                        $scope.lasTran[i].monto = parseFloat($scope.lasTran[i].monto);
                        $scope.lasTran[i].operado = parseInt($scope.lasTran[i].operado);
                        $scope.lasTran[i].anticipo = parseInt($scope.lasTran[i].anticipo);
                    }
                });
            }
        };

        $scope.cleanInfo = function(){
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
        };

        $scope.resetLaTran = function(){
            $scope.laTran = {
                objBanco: $scope.laTran.objBanco != null && $scope.laTran.objBanco != undefined ? $scope.laTran.objBanco : undefined,
                fecha: moment().toDate(),
                concepto: '', 
                anticipo: 0, 
                idbeneficiario: 0, 
                tipocambio: parseFloat('1').toFixed($scope.dectc),
                esnegociable: 0,
                iddetpresup: undefined,
                iddetpagopresup: undefined,
                idproyecto: undefined
            };
            $scope.lasTran = [];
            $scope.losDocsSoporte = [];
            $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
            $scope.losDetCont = [];
            $scope.elDetCont = {debe: 0.0, haber: 0.0};
            $scope.strTran = '';
            $scope.editando = false;
        };

        $scope.getNumCheque = function(){
            if($scope.laTran.objBanco != null && $scope.laTran.objBanco != undefined){
                if($scope.laTran.objBanco.id != null && $scope.laTran.objBanco.id != undefined){
                    if($scope.laTran.objTipotrans.abreviatura === 'C'){
                        bancoSrvc.getCorrelativoBco(parseInt($scope.laTran.objBanco.id)).then(function(c){ $scope.laTran.numero = parseInt(c[0].correlativo)});
                    }else{
                        $scope.laTran.numero = 0;
                        $scope.laTran.idproyecto = undefined;
                    }
                }
            }            
        };

        $scope.chkFechaEnPeriodo = function(qFecha, deDonde){
            if(angular.isDate(qFecha)){
                if(qFecha.getFullYear() >= 2000){
                    //console.log(qFecha);
                    periodoContableSrvc.validaFecha(moment(qFecha).format('YYYY-MM-DD')).then(function(d){
                        var fechaValida = parseInt(d.valida) === 1;
                        if(!fechaValida){
                            var cualFecha = '';
                            var tipo = '';
                            switch(deDonde){
                                case 't' :
                                    $scope.laTran.fecha = null;
                                    cualFecha = 'de la transacción';
                                    tipo = 'error';
                                    break;
                                case 'd' :
                                    $scope.elDocSop.fechadoc = null;
                                    cualFecha = 'del documento de soporte';
                                    tipo = 'warning';
                                    break;
                            }
                            toaster.pop({ type:''+ tipo +'', title: 'Fecha '+ cualFecha +' es inválida.',
                                body: 'No está dentro de ningún período contable abierto.', timeout: 7000 });


                        }
                    });
                }
            }
        };

        $scope.setNombreBene = function(bene){
            if(!$scope.laTran.beneficiario || $scope.laTran.beneficiario.trim() == ''){
                $scope.laTran.beneficiario = bene != null && bene != undefined ?  bene.chequesa : '';
            }
        };

        $scope.getDocs = function(td){
            switch(parseInt(td.id)){
                case 1: tranBancSrvc.lstFactCompra($scope.laTran.idbeneficiario, $scope.laTran.id).then(function(d){ $scope.compraspendientes = d; }); break;
                case 2: tranBancSrvc.lstReembolsos($scope.laTran.idbeneficiario).then(function(d){ $scope.compraspendientes = d; }); break;
            }
        };

        $scope.setData = function(ds){
            $scope.elDocSop.fechadoc = moment(ds.fechafactura).toDate();
            $scope.elDocSop.serie = ds.serie;
            $scope.elDocSop.documento = ds.documento;
            $scope.elDocSop.monto = parseFloat(ds.totfact);

            if(parseFloat($scope.laTran.monto) != parseFloat($scope.elDocSop.monto)){
                toaster.pop({
                    type: 'warning',
                    title: 'Advertencia.',
                    body: 'El monto de la transacción (' + parseFloat($scope.laTran.monto).toFixed(2) +
                    ') no cuadra con el monto del documento de soporte (' + parseFloat($scope.elDocSop.monto).toFixed(2) + ').',
                    timeout: 7000
                });
            }
        };

        $scope.fillData = function(item, model){
            //console.log(item);
            //var tmpObjBene = $filter('filter')($scope.beneficiarios, {id:item.idproveedor, dedonde:"1"}, true);
            var tmpObjBene = $filter('filter')($scope.beneficiarios, {id:item.idproveedor, dedonde:item.origenprov}, true);
            $scope.laTran.anticipo = 1;
            $scope.laTran.objBeneficiario = tmpObjBene.length > 0 ? tmpObjBene[0] : undefined;
            $scope.setNombreBene($scope.laTran.objBeneficiario);

            if(item && item.notas && item.notas.trim() !== ''){
                if(!$scope.laTran.concepto || $scope.laTran.concepto.trim() == ''){
                    $scope.laTran.concepto = item.notas.trim();
                }
            }else{
                $scope.laTran.concepto = $scope.laTran.objBeneficiario != null && $scope.laTran.objBeneficiario != undefined ? $scope.laTran.objBeneficiario.concepto : undefined;
            }

            //$scope.laTran.idproyecto = item.idproyecto;

            $scope.laTran.tipocambio = parseFloat(item.tipocambio).toFixed($scope.dectc);
            $scope.laTran.monto = (+$scope.laTran.objBanco.idmoneda != +item.idmoneda) ? parseFloat(parseFloat(item.valor) * parseFloat($scope.laTran.tipocambio)).toFixed(2) : parseFloat(item.valor).toFixed(2);
            $scope.laTran.iddetpresup = item.id;
        };

        $scope.fillDataOnChangeBene = function(item, model){
            $scope.setNombreBene(item);
            if(!$scope.laTran.concepto || $scope.laTran.concepto.trim() == ''){
                $scope.laTran.concepto = item.concepto;
            }
        };

        $scope.addTran = function(obj){
            obj.idbanco = obj.objBanco.id;
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.tipotrans = obj.objTipotrans.abreviatura;
            obj.anticipo = obj.anticipo != null && obj.anticipo != undefined ? obj.anticipo : 0;
            obj.esnegociable = obj.esnegociable != null && obj.esnegociable != undefined ? obj.esnegociable : 0;
            obj.esnegociable = obj.tipotrans.toUpperCase() == 'C' ? obj.esnegociable : 0;
            obj.idbeneficiario = (parseInt(obj.anticipo) === 0) ? 0 : (obj.objBeneficiario != null && obj.objBeneficiario != undefined ? obj.objBeneficiario.id : 0);
            obj.origenbene = (parseInt(obj.anticipo) === 0) ? 0 : (obj.objBeneficiario != null && obj.objBeneficiario != undefined ? obj.objBeneficiario.dedonde : 0);
            obj.iddetpresup = obj.iddetpresup != null && obj.iddetpresup != undefined ? obj.iddetpresup : 0;
            obj.iddetpagopresup = obj.iddetpagopresup != null && obj.iddetpagopresup != undefined ? obj.iddetpagopresup : 0;
            obj.idproyecto = obj.idproyecto != null && obj.idproyecto != undefined ? obj.idproyecto : 0;
            //console.log(obj); return;
            tranBancSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstTran();
                $scope.getDataTran(parseInt(d.lastid));
            });
        };

        function processData(data){
            for(var i = 0; i < data.length; i++){
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
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed($scope.dectc));
                data[i].impreso = parseInt(data[i].impreso);
                data[i].fechaliquida = moment(data[i].fechaliquida).isValid() ? moment(data[i].fechaliquida).toDate() : null;
                data[i].iddetpagopresup = +data[i].iddetpagopresup == 0 ? undefined : data[i].iddetpagopresup;
            }
            return data;
        }

        function procDataDocs(data){
            for(var i = 0; i < data.length; i++){
                data[i].idtipodoc = parseInt(data[i].idtipodoc);
                data[i].fechadoc = moment(data[i].fechadoc).toDate();
                data[i].documento = parseInt(data[i].documento);
                data[i].monto = parseFloat(data[i].monto);
                data[i].iddocto = parseInt(data[i].iddocto);
                //data[i].fechaliquida = moment(data[i].fechaliquida).isValid() ? moment(data[i].fechaliquida).toDate() : null;
            }
            return data;
        }

        function procDataDet(data){
            for(var i = 0; i < data.length; i++){
                data[i].debe = parseFloat(data[i].debe);
                data[i].haber = parseFloat(data[i].haber);
            }
            return data;
        }

        $scope.checkTotales = function(idtran){
            var totTran = parseFloat(parseFloat($scope.laTran.monto).toFixed(2));
            detContSrvc.getSumaPartida(1, idtran).then(function(d){
                var sumdebe = parseFloat(parseFloat(d.sumdebe).toFixed(2)), sumhaber = parseFloat(parseFloat(d.sumhaber).toFixed(2))
                if(totTran === sumdebe && totTran === sumhaber && sumdebe === sumhaber){
                    $scope.hayDescuadre = false;
                }else{
                    $scope.hayDescuadre = true;
                }
            });

        };

        $scope.getLiquidacion = function(idtran){
            detContSrvc.lstDetalleCont($scope.origenLiq, idtran).then(function(liq){
                $scope.liquidacion = procDataDet(liq);
                goTop();
            });
        };

        $scope.getDetCont = function(idtran){
            detContSrvc.lstDetalleCont($scope.origen, idtran).then(function(detc){
                $scope.losDetCont = procDataDet(detc);
                $scope.getLiquidacion(idtran);
                $scope.checkTotales(+idtran);
                goTop();
            });
        };

        function getByIdOrigen(input, id, origen) {
            for(var i = 0; i < input.length; i++) { if (+input[i].id == +id && +input[i].dedonde == +origen) { return input[i]; } }
            return null;
        }

        function formatoNumero(numero, decimales){ return $filter('number')(numero, decimales); }

        $scope.getDataTran = function(idtran){
            $scope.editando = true;
            $scope.liquidacion = [];
            presupuestoSrvc.lstPagosOt().then(function(d){ $scope.ots = d; });
            tranBancSrvc.getTransaccion(parseInt(idtran)).then(function(d){
                $scope.laTran = processData(d)[0];
                //console.log($scope.laTran);
                $scope.laTran.objBanco = $filter('getById')($scope.losBancos, $scope.laTran.idbanco);

                var tmp = $scope.laTran, coma = ', ';

                $scope.strTran  = (tmp.anticipo === 0 ? '' : 'Anticipo, ') + tmp.objBanco.nombre + ' (' + tmp.objBanco.nocuenta + ')' + coma;
                $scope.strTran += tmp.tipotrans + '-' + tmp.numero + coma;
                $scope.strTran += moment(tmp.fecha).format('DD/MM/YYYY') + coma + tmp.moneda + ' ' + formatoNumero(tmp.monto, 2) + coma + tmp.beneficiario;

                if($scope.laTran.anticipo === 1){
                    //$scope.laTran.objBeneficiario = [getByIdOrigen($scope.beneficiarios, $scope.laTran.idbeneficiario, $scope.laTran.origenbene)];
                    var tmpObjBene = $filter('filter')($scope.beneficiarios, {id: $scope.laTran.idbeneficiario, dedonde: $scope.laTran.origenbene}, true);
                    $scope.laTran.objBeneficiario = tmpObjBene.length > 0 ? tmpObjBene[0] : undefined ;
                }

                tipoMovTranBanSrvc.getByAbreviatura(d[0].tipotrans).then(function(res){
                    $scope.laTran.objTipotrans = res[0];
                    tipoDocSopTBSrvc.lstTiposDocTB(parseInt(res[0].id)).then(function(d){ $scope.losTiposDocTB = d; });
                });

                tranBancSrvc.lstDocsSoporte(parseInt(idtran)).then(function(det){
                    $scope.losDocsSoporte = procDataDocs(det);
                    $scope.compraspendientes = [];
                    $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
                });

                cuentacSrvc.getByTipo($scope.laEmpresa.id, 0).then(function(ctas){
                    $scope.lasCuentasMov = ctas;
                });

                $scope.getDetCont(parseInt(idtran));

            });
        };

        $scope.gcprint = function(obj){
            var gadget = new cloudprint.Gadget();
            //var url = "http://52.35.3.1/sayet/php/" + obj.objBanco.formato + ".php?c=" + obj.id;
            var url = window.location.origin + "/sayet/php/" + obj.objBanco.formato + ".php?c=" + obj.id + "&uid=" + $scope.uid;
            console.log(url);
            gadget.setPrintDocument("url", "C" + obj.numero, url);
            gadget.openPrintDialog();
        };

        $scope.modalPRINT = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalPRINT.html',
                controller: 'ModalPrin',
                resolve:{
                    venta: function(){return $scope.venta;},
                    objbancos:function(){return obj;},
                    userid:function(){return $scope.uid}
                }
            });
        };

        $scope.updTran = function(data, id){
            data.idbanco = data.objBanco.id;
            data.fechastr = moment(data.fecha).format('YYYY-MM-DD');
            data.tipotrans = data.objTipotrans.abreviatura;
            data.anticipo = data.anticipo != null && data.anticipo != undefined ? data.anticipo : 0;
            data.esnegociable = data.esnegociable != null && data.esnegociable != undefined ? data.esnegociable : 0;
            data.esnegociable = data.tipotrans.toUpperCase() == 'C' ? data.esnegociable : 0;
            data.idbeneficiario = (parseInt(data.anticipo) === 0) ? 0 : (data.objBeneficiario != null && data.objBeneficiario != undefined ? data.objBeneficiario.id : 0);
            data.origenbene = (parseInt(data.anticipo) === 0) ? 0 : (data.objBeneficiario != null && data.objBeneficiario != undefined ? data.objBeneficiario.dedonde : 0);
            data.iddetpresup = data.iddetpresup != null && data.iddetpresup != undefined ? data.iddetpresup : 0;
            data.iddetpagopresup = data.iddetpagopresup != null && data.iddetpagopresup != undefined ? data.iddetpagopresup : 0;
            data.idproyecto = data.idproyecto != null && data.idproyecto != undefined ? data.idproyecto : 0;
            tranBancSrvc.editRow(data, 'u').then(function(){
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
            /*
            $confirm({text: 'Este proceso eliminará el detalle contable que ya se haya ingresado y se creará uno nuevo. ¿Seguro(a) de continuar?',
                title: 'Actualización de transacción bancaria', ok: 'Sí', cancel: 'No'}).then(function() {
            });
            */
        };

        $scope.delTran = function(obj){
            $confirm({
                text: '¿Seguro(a) de eliminar esta transacción? (Se liberarán los documentos de soporte, se eliminará el detalle contable de esta transacción y, en el caso de los cheques, se reseteará el correlativo a este número)',
                title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                tranBancSrvc.editRow({ id: obj.id }, 'd').then(function(){ $scope.getLstTran(); $scope.resetLaTran(); });
            });
        };

        $scope.anular = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAnulacion.html',
                controller: 'ModalAnulacionCtrl',
                resolve:{
                    lstrazonanula: function(){
                        return $scope.razonesanula;
                    }
                }
            });

            modalInstance.result.then(function(datosAnula){
                //console.log(datosAnula);
                obj.idrazonanulacion = datosAnula.idrazonanulacion;
                obj.fechaanulastr = datosAnula.fechaanulastr;
                //console.log(obj);
                tranBancSrvc.editRow(obj, 'anula').then(function(){ $scope.getDataTran($scope.laTran.id); });
            }, function(){ return 0; });
        };

        $scope.getCompras = function(){
            if(+$scope.laTran.id > 0){
                tranBancSrvc.lstCompras(+$scope.laTran.id).then(function(d){ $scope.compras = d; });
            }else{
                $scope.compras = [];
            }
        };

        $scope.addDocSop = function(obj){
            obj.idtranban = parseInt($scope.laTran.id);
            obj.fechadocstr = moment(obj.fechadoc).format('YYYY-MM-DD');
            obj.idtipodoc = obj.objTipoDocTB.id;
            obj.serie = obj.serie != null && obj.serie != undefined ? obj.serie : '';
            obj.iddocto = obj.objDocsPendientes[0] != null && obj.objDocsPendientes[0] != undefined ? obj.objDocsPendientes[0].id : 0;
            obj.montotran = $scope.laTran.monto;
            obj.idempresa = $scope.laEmpresa.id;
            obj.fechaliquidastr = moment(obj.fechaliquida).isValid() ? moment(obj.fechaliquida).format('YYYY-MM-DD') : '';

            tranBancSrvc.getSumDocsSop(parseInt($scope.laTran.id)).then(function(suma){
                suma.totmonto = suma.totmonto != null && suma.totmonto != undefined ? suma.totmonto : 0.0;
                var totMonto = parseFloat(suma.totmonto) + parseFloat(obj.monto);
                //if(totMonto <= parseFloat($scope.laTran.monto)){
                    tranBancSrvc.editRow(obj, 'cd').then(function(){
                        tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                            $scope.losDocsSoporte = procDataDocs(det);
                        });
                        $scope.elDocSop = {fechadoc: moment().toDate(), fechaliquida: null};
                        $scope.getDetCont(obj.idtranban);
                        $scope.getLiquidacion(obj.idtranban);
                    });
                //}else{
                    //toaster.pop({ type: 'error', title: 'Error en el monto.',
                        //body: 'La suma de los montos de los documentos de soporte no puede ser mayor al monto de la transacción.', timeout: 7000 });
                    //$scope.elDocSop.monto = null;
                //};
            });
        };

        $scope.zeroDebe = function(valor){ $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function(valor){ $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        $scope.addDetCont = function(obj){
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.laTran.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta.id);
            detContSrvc.editRow(obj, 'c').then(function(){
                detContSrvc.lstDetalleCont($scope.origen, parseInt($scope.laTran.id)).then(function(detc){
                    $scope.losDetCont = procDataDet(detc);
                    $scope.elDetCont = {debe: 0.0, haber: 0.0};
                    $scope.searchcta = "";
                    $scope.checkTotales(+$scope.laTran.id);
                });
            });
        };

        $scope.loadDetaCont = function(){
            detContSrvc.lstDetalleCont($scope.origen, +$scope.laTran.id).then(function(detc){
                $scope.losDetCont = procDataDet(detc);
                $scope.elDetCont = {debe: 0.0, haber: 0.0};
                $scope.getLiquidacion(+$scope.laTran.id);
                $scope.checkTotales(+$scope.laTran.id);
            });
        };

        $scope.updDetCont = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalUpdDetCont.html',
                controller: 'ModalUpdDetContCtrl',
                resolve:{
                    detalle: function(){ return obj; },
                    idempresa: function(){return +$scope.laEmpresa.id; }
                }
            });

            modalInstance.result.then(function(){
                $scope.loadDetaCont();
            }, function(){ $scope.loadDetaCont(); });
        };

        $scope.delDetCont = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.getDetCont(obj.idorigen); $scope.checkTotales(+obj.idorigen); });
            });
        };

        $scope.printVersion = function(){
            //PrintElem('#toPrint', 'Transacción bancaria');
            var test = false;
            tranBancSrvc.imprimir(+$scope.laTran.id).then(function(d){
                jsReportSrvc.getPDFReport(test ? 'r1V2bJYkW' : 'rJStGGt1-', d).then(function(pdf){
                    $window.open(pdf);
                });
            });
        };

        $scope.updateDetRecCli = function(obj){
            $confirm({text: '¿Seguro(a) de actualizar monto aplicado de este documento?', title: 'Modificación', ok: 'Sí', cancel: 'No'}).then(function() {

                tranBancSrvc.editRow({idtipodoc:obj.idtipodoc, documento:obj.documento, fechadocstr:obj.fechadoc, serie:obj.serie, iddocto:obj.iddocto, id: obj.id, monto: obj.monto}, 'ud').then(function(){
                    tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                        $scope.losDocsSoporte = procDataDocs(det);
                    });
                });
                $scope.reset(obj);
            });
        };

        $scope.delDetRecCli = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este documento? (Esto dejará como pendiente el documento)', title: 'Eliminar documento rebajado', ok: 'Sí', cancel: 'No'}).then(function() {
                tranBancSrvc.editRow({id: obj.id, iddocto: obj.iddocto}, 'dd').then(function(){
                    tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                        $scope.losDocsSoporte = procDataDocs(det);
                    });
                });
            });
        };
        $scope.editDetRecCli = function(obj){
            $scope.selected = angular.copy(obj);
        };

        $scope.getTemplate = function (obj) {
            if (obj.id === $scope.selected.id){
                return 'edit';
            }
            else return 'display';
        };

        $scope.reset = function (obj) {
            $scope.selected = {};
            tranBancSrvc.lstDocsSoporte(parseInt($scope.laTran.id)).then(function(det){
                $scope.losDocsSoporte = procDataDocs(det);
            });
        };

    }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalAnulacionCtrl', ['$scope', '$uibModalInstance', 'lstrazonanula', function($scope, $uibModalInstance, lstrazonanula){
        $scope.razones = lstrazonanula;
        $scope.razon = [];
        $scope.anuladata = {idrazonanulacion:0, fechaanula: moment().toDate()};

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
    tranbancctrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm){
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

    //Controlador de formulario de impresion cheques continuos
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    tranbancctrl.controller('ModalPrin', ['$scope', '$uibModalInstance', 'venta' , 'userid' , 'objbancos',  'tranBancSrvc', function($scope, $uibModalInstance, venta, userid, objbancos,  tranBancSrvc){
        $scope.venta = venta;
        $scope.losBancos = objbancos;
        $scope.correlativos=[];

        /*console.log('idusuario',userid);
         console.log('bancos:',$scope.losBancos);*/

        $scope.ok = function () {
            $scope.venta.ndel = $scope.venta.ndel != null && $scope.venta.ndel != undefined ? $scope.venta.ndel : '';
            $scope.venta.nal = $scope.venta.nal != null && $scope.venta.nal != undefined ? $scope.venta.nal : '';
            $scope.venta.idbanco = $scope.losBancos.id.id!= null && $scope.losBancos.id.id!= undefined ? $scope.losBancos.id.id: '';
            //$scope.venta.idempresa= idempresa;
            tranBancSrvc.lstCorrelativos($scope.venta.ndel,$scope.venta.nal,$scope.venta.idbanco ).then(function(d)
            {
                $scope.correlativos = d;
                $uibModalInstance.close();
                //console.log('datos recibidos',d);
                tranBancSrvc.editRow($scope.venta, 'udoc').then(function(){});
            });
            /*console.log('datos del banco',$scope.losBancos.id.id);
             console.log('datos enviados',$scope.venta);*/
            $scope.formularioid = true;

            //var url = window.location.origin + "/sayet/php/" + $scope.losBancos.id.formato + "continuo.php?idbanco=" + $scope.venta.idbanco + "&uid=" + userid + "&c_del=" + $scope.venta.ndel + "&c_al=" + $scope.venta.nal;
			var url = window.location.origin + "/sayet/php/printcheckcontinuo.php?idbanco=" + $scope.venta.idbanco + "&uid=" + userid + "&c_del=" + $scope.venta.ndel + "&c_al=" + $scope.venta.nal;
            //console.log(url);
            window.open(url);

        };
        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);

}());
