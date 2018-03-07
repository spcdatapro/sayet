(function(){

    var reembolsoctrl = angular.module('cpm.reembolsoctrl', []);

    reembolsoctrl.controller('reembolsoCtrl', ['$scope', 'reembolsoSrvc', 'monedaSrvc', 'authSrvc', 'empresaSrvc', '$route', '$confirm', 'tipoReembolsoSrvc', 'DTOptionsBuilder', '$filter', 'tipoFacturaSrvc', 'tipoCompraSrvc', 'detContSrvc', 'cuentacSrvc', 'toaster', '$uibModal', 'tipoMovTranBanSrvc', 'bancoSrvc', 'beneficiarioSrvc', 'tipoCombustibleSrvc', 'proveedorSrvc', 'localStorageSrvc', '$location', 'proyectoSrvc', 'tipogastoSrvc', function($scope, reembolsoSrvc, monedaSrvc, authSrvc, empresaSrvc, $route, $confirm, tipoReembolsoSrvc, DTOptionsBuilder, $filter, tipoFacturaSrvc, tipoCompraSrvc, detContSrvc, cuentacSrvc, toaster, $uibModal, tipoMovTranBanSrvc, bancoSrvc, beneficiarioSrvc, tipoCombustibleSrvc,proveedorSrvc, localStorageSrvc, $location, proyectoSrvc, tipogastoSrvc){

        $scope.monedas = [];
        $scope.dectc = 2;
        $scope.permiso = {};
        $scope.reembolsos = [];
        $scope.reembolso = {};
        $scope.tiposreembolso = [];
        $scope.compras = [];
        $scope.compra = {};
        $scope.tiposfactura = [];
        $scope.tiposcompra = [];
        $scope.reemstr = '';
        $scope.comprastr = '';
        $scope.origen = 2;
        $scope.detcont = {debe:0.00, haber:0.00};
        $scope.cuentasc = [];
        $scope.beneficiarios = [];
        $scope.tiposmov = [];
        $scope.bancos = [];
        $scope.detcontreem = [];
        $scope.origenReembolsos = 5;
        $scope.tranban = [];
        $scope.dataToPrint = [];
        $scope.total = {debe: 0.00, haber: 0.00};
        $scope.combustibles = [];
        $scope.infocompras = {cantidad: 0, sumtotfact: 0.00}
        $scope.uid = 0;
        $scope.proyectos = [];
        $scope.subtiposgasto = [];

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap()
            .withBootstrapOptions({
                pagination: {
                    classes: {
                        ul: 'pagination pagination-sm'
                    }
                }
            })
            .withOption('ordering', true)
            .withOption('responsive', true);

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
            .withOption('ordering', false);
            //.withOption('fnRowCallback', rowCallback);

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.uid = +usrLogged.uid;
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){
                    $scope.permiso = d;
                    empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                        //$scope.reembolso.objEmpresa = d[0];
                        $scope.reembolso.idempresa = parseInt(d[0].id);
                        $scope.dectc = parseInt(d[0].dectc);
                        proyectoSrvc.lstProyectosPorEmpresa($scope.reembolso.idempresa).then(function(d){
                            $scope.proyectos = d;                            
                            $scope.resetReembolso();
                            $scope.resetCompra();
                            $scope.getLstReembolsos();
                        });                        
                    });
                });
            }
        });

        tipoReembolsoSrvc.lstTiposReembolso().then(function(d){ $scope.tiposreembolso = d; });

        tipoFacturaSrvc.lstTiposFactura().then(function(d){
            for(var i = 0; i < d.length; i++){
                d[i].generaiva = parseInt(d[i].generaiva) === 1;
                d[i].paracompra = parseInt(d[i].paracompra);
            }
            $scope.tiposfactura = d;
        });

        tipoCompraSrvc.lstTiposCompra().then(function(d){ $scope.tiposcompra = d; });

        beneficiarioSrvc.lstBeneficiarios().then(function(d){ $scope.beneficiarios = d; });

        tipoCombustibleSrvc.lstTiposCombustible().then(function(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].impuesto = parseFloat(parseFloat(d[i].impuesto).toFixed(2));
            }
            $scope.combustibles = d;
        });

        tipogastoSrvc.lstSubTipoGasto().then(function(d){ $scope.subtiposgasto = d; });

        function procDataReemb(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].finicio = moment(d[i].finicio).toDate();
                d[i].ffin = !moment(d[i].ffin).isValid() ? null : moment(d[i].ffin).toDate();
                d[i].estatus = parseInt(d[i].estatus);
                d[i].idtiporeembolso = parseInt(d[i].idtiporeembolso);
                d[i].idbeneficiario = parseInt(d[i].idbeneficiario);
                d[i].totreembolso = parseFloat(parseFloat(d[i].totreembolso).toFixed(2));
                d[i].fondoasignado = parseFloat(parseFloat(d[i].fondoasignado).toFixed(2));
                d[i].idsubtipogasto = parseInt(d[i].idsubtipogasto) == 0 ? undefined : d[i].idsubtipogasto;
            }
            return d;
        }

        function procDataCompras(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].idreembolso = parseInt(d[i].idreembolso);
                d[i].idtipofactura = parseInt(d[i].idtipofactura);
                d[i].documento = parseInt(d[i].documento);
                d[i].fechaingreso = moment(d[i].fechaingreso).toDate();
                d[i].mesiva = parseInt(d[i].mesiva);
                d[i].fechafactura = moment(d[i].fechafactura).toDate();
                d[i].idtipocompra = parseInt(d[i].idtipocompra);
                d[i].idtipocombustible = parseInt(d[i].idtipocombustible);
                d[i].revisada = parseInt(d[i].revisada);
                d[i].totfact = parseFloat(d[i].totfact).toFixed(2);
                d[i].subtotal = parseFloat(d[i].subtotal).toFixed(2);
                d[i].iva = parseFloat(d[i].iva).toFixed(2);
                d[i].idmoneda = parseInt(d[i].idmoneda);
                d[i].tipocambio = parseFloat(d[i].tipocambio).toFixed($scope.dectc);
                d[i].idproveedor = parseInt(d[i].idproveedor);
                d[i].retenerisr = parseInt(d[i].retenerisr);
                d[i].isr = parseFloat(d[i].isr).toFixed(2);
                d[i].idp = parseFloat(d[i].idp).toFixed(2);
                d[i].galones = parseFloat(d[i].galones).toFixed(2);
                d[i].detcont = [];
            }
            return d;
        }

        $scope.resetReembolso = function(){
            $scope.reembolso = {
                idempresa: $scope.reembolso.idempresa,
                finicio: moment().toDate(),
                ffin: null,
                beneficiario: '',
                idbeneficiario: 0,
                tblbeneficiario: '',
                estatus: 1,
                fondoasignado: undefined,
                idsubtipogasto: undefined
            };
            $scope.reemstr = '';
            $scope.resetCompra();
            $scope.detcontreem = [];
            $scope.tranban = [];
            goTop();
        };

        $scope.resetCompra = function(){
            $scope.compra = {
                idempresa: parseInt($scope.reembolso.idempresa),
                idreembolso: 0,
                idproveedor: 0,
                proveedor: '',
                nit: '',
                fechaingreso: moment().toDate(),
                mesiva: moment().month() + 1,
                fechafactura: moment().toDate(),
                idtipocompra: 0,
                totfact: 0.00,
                noafecto: 0.00,
                subtotal: 0.00,
                iva: 0.00,
                idmoneda: 1,
                tipocambio: parseFloat('1').toFixed($scope.dectc),
                objTipoFactura: [],
                conceptomayor: '',
                retenerisr: 0,
                isr: 0.00,
                objTipoCombustible: [],
                idtipocombustible: 0,
                galones: 0.00,
                idp: 0.00,
                revisada: 0,
                idproyecto: undefined,
                idsubtipogasto: $scope.reembolso ? ($scope.reembolso.objTipoReembolso && +$scope.reembolso.objTipoReembolso.id == 1 ? $scope.reembolso.idsubtipogasto : undefined) : undefined
            };
            //console.log($scope.compra);
            $scope.comprastr = '';
            $scope.$broadcast('angucomplete-alt:clearInput', 'txtNit');
            goTop();
        };

        $scope.getLstReembolsos = function(){
            reembolsoSrvc.lstReembolsos($scope.reembolso.idempresa).then(function(d){
                $scope.reembolsos = procDataReemb(d);
            });
        };

        $scope.getDetReem = function(idreem){
            reembolsoSrvc.lstCompras(idreem).then(function(d){
                $scope.compras = procDataCompras(d);
                cuentacSrvc.getByTipo($scope.reembolso.idempresa, 0).then(function(d){ $scope.cuentasc = d; });

                $scope.infocompras.cantidad = $scope.compras.length;
                $scope.infocompras.sumtotfact = 0.00;
                for(var i = 0; i < $scope.infocompras.cantidad; i++){
                    $scope.infocompras.sumtotfact += parseFloat($scope.compras[i].totfact);
                }

                $scope.infocompras.sumtotfact = parseFloat(parseFloat($scope.infocompras.sumtotfact).toFixed(2));

            });
        };

        $scope.getReembolso = function(idreembolso){
            $scope.resetCompra();
            $scope.detcontreem = [];
            reembolsoSrvc.getReembolso(idreembolso).then(function(d){
                $scope.reembolso = procDataReemb(d)[0];
                $scope.reembolso.objTipoReembolso = $filter('getById')($scope.tiposreembolso, $scope.reembolso.idtiporeembolso);
                $scope.reembolso.objBeneficiario = [$filter('getById')($scope.beneficiarios, $scope.reembolso.idbeneficiario)];
                $scope.reemstr = $scope.reembolso.tipo + ', No. ' + $filter('padNumber')(idreembolso, 5) + ', Iniciando el ' + moment($scope.reembolso.finicio).format('DD/MM/YYYY') + ', ' + $scope.reembolso.beneficiario;
                $scope.getDetReem($scope.reembolso.id);
                bancoSrvc.lstBancosActivos($scope.reembolso.idempresa).then(function(d){ $scope.bancos = d; });
                tipoMovTranBanSrvc.getBySuma(0).then(function(d){ $scope.tiposmov = d; });
                reembolsoSrvc.getTranBan(idreembolso).then(function(d){ $scope.tranban = d; });
                detContSrvc.lstDetalleCont($scope.origenReembolsos, idreembolso).then(function(d){
                    for(var i = 0; i < d.length; i++){
                        d[i].debe = parseFloat(parseFloat(d[i].debe).toFixed(2));
                        d[i].haber = parseFloat(parseFloat(d[i].haber).toFixed(2));
                    }
                    $scope.detcontreem = d;
                });
                $scope.resetCompra();
                goTop();
            });
        };

        $scope.addReembolso = function(obj){
            //obj.idempresa = obj.idempresa;
            obj.finiciostr = moment(obj.finicio).format('YYYY-MM-DD');
            obj.ffinstr = !moment(obj.ffin).isValid() ? '' : moment(obj.ffin).format('YYYY-MM-DD');
            obj.idbeneficiario = obj.objBeneficiario[0].id;
            obj.tblbeneficiario = obj.tblbeneficiario != null && obj.tblbeneficiario != undefined ? obj.tblbeneficiario : '';
            obj.estatus = 1;
            obj.idtiporeembolso = obj.objTipoReembolso.id;
            obj.fondoasignado = obj.fondoasignado != null && obj.fondoasignado != undefined && +obj.idtiporeembolso == 2 ? obj.fondoasignado : 0.00;
            obj.idsubtipogasto = obj.idsubtipogasto != null && obj.idsubtipogasto != undefined && +obj.idtiporeembolso == 1 ? obj.idsubtipogasto : 0;
            reembolsoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstReembolsos();
                $scope.getReembolso(parseInt(d.lastid));
            });
        };

        $scope.updReembolso = function(obj){
            obj.finiciostr = moment(obj.finicio).format('YYYY-MM-DD');
            obj.ffinstr = !moment(obj.ffin).isValid() ? '' : moment(obj.ffin).format('YYYY-MM-DD');
            obj.idbeneficiario = obj.objBeneficiario[0].id;
            obj.tblbeneficiario = obj.tblbeneficiario != null && obj.tblbeneficiario != undefined ? obj.tblbeneficiario : '';
            obj.idtiporeembolso = obj.objTipoReembolso.id;
            obj.fondoasignado = obj.fondoasignado != null && obj.fondoasignado != undefined && +obj.idtiporeembolso == 2 ? obj.fondoasignado : 0.00;
            obj.idsubtipogasto = obj.idsubtipogasto != null && obj.idsubtipogasto != undefined && +obj.idtiporeembolso == 1 ? obj.idsubtipogasto : 0;
            reembolsoSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstReembolsos();
            });
        };

        $scope.delReembolso = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este reembolso? (También se eliminará su detalle)',
                title: 'Eliminar reembolso', ok: 'Sí', cancel: 'No'}).then(function() {
                reembolsoSrvc.editRow({id: obj.id, origen: $scope.origen}, 'd').then(function(){
                    $scope.getLstReembolsos();
                    $scope.resetReembolso();
                });
            });
        };

        function esCombustible(){
            if($scope.compra.objTipoCompra != null && $scope.compra.objTipoCompra != undefined){
                if($scope.compra.objTipoCompra.id != null && $scope.compra.objTipoCompra.id != undefined){
                    if(parseInt($scope.compra.objTipoCompra.id) == 3){
                        return true;
                    }
                }

            }
            return false;
        }

        $scope.esrec = function(){
            if($scope.compra.objTipoFactura != null && $scope.compra.objTipoFactura != undefined){
                if(+$scope.compra.objTipoFactura.id == 5){
                    $scope.$broadcast('angucomplete-alt:changeInput', 'txtNit', {nit: 'CF', proveedor: ''});
                    $scope.compra.serie = 'REC';
                }else{
                    if($scope.compra.serie != null && $scope.compra.serie !== undefined){
                        if($scope.compra.serie.trim() == 'REC'){
                            $scope.compra.serie = undefined;
                        }
                    }
                    if($scope.compra.nit != null && $scope.compra.nit !== undefined){
                        if($scope.compra.nit.toUpperCase().trim() == 'CF'){
                            $scope.$broadcast('angucomplete-alt:clearInput', 'txtNit');
                            $scope.compra.proveedor = undefined;
                        }
                    }
                }
            }
        };

        function calcIDP(genidp){
            if(genidp){
                var galones = $scope.compra.galones != null && $scope.compra.galones != undefined ? parseFloat($scope.compra.galones) : 0.00;
                var impuesto = $scope.compra.objTipoCombustible.impuesto != null && $scope.compra.objTipoCombustible.impuesto != undefined ? parseFloat($scope.compra.objTipoCombustible.impuesto) : 0.00;
                //console.log(galones); console.log(impuesto); console.log((galones * impuesto).toFixed(2));
                return (galones * impuesto).toFixed(2);
            }
            return 0.00;
        }

        $scope.calcIVA = function(obj){
            var total = 0.00, noafecto = 0.00, subtotal = 0.00, genidp = esCombustible(), idp = 0.00, exento = 0.00, isr = 0.00;
            $scope.compra.idp = calcIDP(genidp);
            if(obj.objTipoFactura.generaiva && obj.totfact != null && obj.totfact != undefined){
                total = parseFloat(parseFloat($scope.compra.totfact).toFixed(2));
                //console.log('TOTAL = ' + total);
                noafecto = parseFloat(parseFloat($scope.compra.noafecto).toFixed(2));
                //console.log('NO AFECTO = ' + noafecto);
                idp = parseFloat(parseFloat($scope.compra.idp).toFixed(2));
                //console.log('IDP = ' + idp);
                isr = parseFloat(parseFloat($scope.compra.isr).toFixed(2));
                //console.log('ISR = ' + isr);
                exento = idp + noafecto;
                //console.log('EXENTO = ' + exento);
                subtotal = parseFloat((total - exento).toFixed(2));
                //console.log('SUBTOTAL = ' + subtotal);
                $scope.compra.subtotal = parseFloat(subtotal / 1.12).toFixed(2);
                $scope.compra.iva = parseFloat($scope.compra.subtotal * 0.12).toFixed(2);
            }else{
                total = parseFloat(parseFloat($scope.compra.totfact).toFixed(2));
                noafecto = parseFloat(parseFloat($scope.compra.noafecto).toFixed(2));
                idp = parseFloat(parseFloat($scope.compra.idp).toFixed(2));
                subtotal = total;
                $scope.compra.subtotal = subtotal;
                $scope.compra.iva = 0.00;
            }
        };

        $scope.calcISR = function(){
            if(+$scope.compra.retenerisr == 1){
                var subtot = parseFloat($scope.compra.subtotal);
                if(subtot > 0){
                    reembolsoSrvc.calculaISR({subtotal: subtot}).then(function(d){
                        $scope.compra.isr = parseFloat(d.isr).toFixed(2);
                    });
                }else{
                    $scope.compra.isr = '0.00';
                }
            }else{
                $scope.compra.isr = '0.00';
            }
        };

        $scope.revisada = function(obj){
            $confirm({text: '¿Seguro(a) de marcar como "revisada" la compra ' + obj.serie + '-' + obj.documento +'?', title: 'Marcar como revisada', ok: 'Sí', cancel: 'No'}).then(function() {
                reembolsoSrvc.setRevisada(obj.id).then(function(){
                    $scope.getDetReem($scope.reembolso.id);
                });
            });
        };

        $scope.setMesIva = function(fing){
            if(fing != null && fing != undefined){
                $scope.compra.mesiva = (moment(fing).month() + 1);
            }else{
                $scope.compra.mesiva = undefined;
            }
        };

        $scope.ctasGastoProv = [];

        $scope.nitSelected = function(item){
            if(item != null && item != undefined){
                switch(typeof item.originalObject){
                    case 'string':
                        $scope.compra.nit = item.originalObject;
                        $scope.compra.proveedor = undefined;
                        $scope.ctasGastoProv = [];
                        break;
                    case 'object':
                        $scope.compra.nit = item.originalObject.nit;
                        $scope.compra.proveedor = item.originalObject.proveedor;
                        proveedorSrvc.getProveedorByNit($scope.compra.nit).then(function(d){
                            var prov = {id:0, concepto: null, retensionisr: 0};
                            if(d.length > 0){ prov = d[0]; }

                            if($scope.compra.conceptomayor != null && $scope.compra.conceptomayor != undefined){
                                if($scope.compra.conceptomayor.length == 0){ $scope.compra.conceptomayor = prov.concepto; }
                            }

                            $scope.compra.retenerisr = $scope.compra.id == null || $scope.compra.id == undefined ? parseInt(prov.retensionisr) : $scope.compra.retenerisr;
                            if(+prov.id > 0){
                                proveedorSrvc.getLstCuentasCont(+prov.id, +$scope.reembolso.idempresa).then(function(lstctas){
                                    $scope.ctasGastoProv = lstctas;
                                });
                            }
                        });
                        break;
                }
            }
        };

        $scope.getCompra = function(obj, evento, subir){
            reembolsoSrvc.getCompra(obj.id).then(function(d){
                $scope.compra = procDataCompras(d)[0];
                $scope.compra.objTipoFactura = $filter('getById')($scope.tiposfactura, $scope.compra.idtipofactura);
                $scope.compra.objTipoCompra = $filter('getById')($scope.tiposcompra, $scope.compra.idtipocompra);
                $scope.compra.objTipoCombustible = $filter('getById')($scope.combustibles, $scope.compra.idtipocombustible);
                $scope.idsubtipogasto = +$scope.idsubtipogasto != 0 ? $scope.idsubtipogasto : undefined;
                $scope.$broadcast('angucomplete-alt:changeInput', 'txtNit', {nit: $scope.compra.nit, proveedor: $scope.compra.proveedor});
                $scope.comprastr = $scope.compra.proveedor + ', ' + $scope.compra.serie + ' '
                    + $scope.compra.documento + ', ' + $scope.compra.simbolo + ' ' + $scope.compra.totfact;
                if(!subir){ goTop(); }
            });
        };

        $scope.existeProveedor = function(nit, nombre){
            obj = {nit: nit, nombre: nombre};
            reembolsoSrvc.editRow(obj, 'existeprov');
            return 0;
        };

        function execUpdate(obj, op){
            //console.log(obj); return;
            reembolsoSrvc.editRow(obj, op).then(function(d){
                $scope.getDetReem($scope.reembolso.id);
                $scope.getCompra({ id: parseInt(d.lastid) });
            });
        }

        function getCuentaGastoProv(obj, op){
            switch(true){
                case $scope.ctasGastoProv.length == 0:
                    obj.ctagastoprov = 0;
                    execUpdate(obj, op);
                    break;
                case $scope.ctasGastoProv.length == 1:
                    obj.ctagastoprov = parseInt($scope.ctasGastoProv[0].idcuentac);
                    execUpdate(obj, op);
                    break;
                case $scope.ctasGastoProv.length > 1:
                    var modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: 'modalSelectCtaGastoProvReem.html',
                        controller: 'ModalCtasGastoProvReemCtrl',
                        resolve:{ lstctasgasto: function(){ return $scope.ctasGastoProv; }
                        }
                    });

                    modalInstance.result.then(function(selectedItem){
                        obj.ctagastoprov = selectedItem.idcuentac;
                        execUpdate(obj, op);
                    }, function(){ idcta = 0; });
                    break;
            }
        }

        $scope.addCompra = function(obj){
            obj.idreembolso = parseInt($scope.reembolso.id);
            obj.idtipofactura = parseInt(obj.objTipoFactura.id);
            obj.fechaingresostr = moment(obj.fechaingreso).format('YYYY-MM-DD');
            obj.fechafacturastr = moment(obj.fechafactura).format('YYYY-MM-DD');
            obj.idtipocompra = parseInt(obj.objTipoCompra.id);
            obj.idtipocombustible = obj.objTipoCombustible != null && obj.objTipoCombustible != undefined ? (obj.objTipoCombustible.id != null && obj.objTipoCombustible.id != undefined ? parseInt(obj.objTipoCombustible.id) : 0) : 0;
            obj.retenerisr = obj.retenerisr != null && obj.retenerisr != undefined ? obj.retenerisr : 0;
            $scope.existeProveedor(obj.nit, obj.proveedor);
            getCuentaGastoProv(obj, 'cd');
            /*
            reembolsoSrvc.editRow(obj, 'cd').then(function(d){
                $scope.getDetReem($scope.reembolso.id);
                $scope.getCompra({id: parseInt(d.lastid)});
            });
            */
        };

        $scope.updCompra = function(obj){
            obj.idtipofactura = parseInt(obj.objTipoFactura.id);
            obj.fechaingresostr = moment(obj.fechaingreso).format('YYYY-MM-DD');
            obj.fechafacturastr = moment(obj.fechafactura).format('YYYY-MM-DD');
            obj.idtipocompra = parseInt(obj.objTipoCompra.id);
            obj.idtipocombustible = obj.objTipoCombustible != null && obj.objTipoCombustible != undefined ? (obj.objTipoCombustible.id != null && obj.objTipoCombustible.id != undefined ? parseInt(obj.objTipoCombustible.id) : 0) : 0;
            obj.retenerisr = obj.retenerisr != null && obj.retenerisr != undefined ? obj.retenerisr : 0;
            $scope.existeProveedor(obj.nit, obj.proveedor);
            getCuentaGastoProv(obj, 'ud');
            /*
            reembolsoSrvc.editRow(obj, 'ud').then(function(){
                $scope.getDetReem($scope.reembolso.id);
            });
            */
        };

        $scope.delCompra = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta compra? (También se eliminará su detalle contable)',
                title: 'Eliminar compra', ok: 'Sí', cancel: 'No'}).then(function() {
                reembolsoSrvc.editRow({id: obj.id, origen: $scope.origen}, 'dd').then(function(){
                    $scope.getDetReem($scope.reembolso.id);
                    $scope.resetCompra();
                });
            });
        };

        $scope.selectCuentaC = function(busqueda){ $scope.detcont.objCuenta = busqueda == '' ? null : $filter('getByCodCta')($scope.cuentasc, busqueda); };

        $scope.zeroDebe = function(valor){ $scope.detcont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.debe; };

        $scope.zeroHaber = function(valor){
            valor = parseFloat(parseFloat(valor).toFixed(2));
            var total = parseFloat(parseFloat($scope.compra.totfact).toFixed(2));
            var iva = parseFloat(parseFloat($scope.compra.iva).toFixed(2));
            var subtot = parseFloat((total - iva).toFixed(2));
            //console.log('Debe = ' + valor + '; Subtotal = ' + subtot);
            //$scope.detcont.debe = subtot;
            if(valor > subtot){
                toaster.pop({ type: 'error', title: 'Error en el monto del debe.',
                    body: 'El monto no puede ser mayor a ' + $filter('number')(subtot, 2) + ' de la factura.', timeout: 7000 });
                $scope.detcont.debe = null;
            }
            $scope.detcont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.detcont.haber;
        };

        $scope.addDetCont = function(obj){
            obj.idorigen = $scope.compra.id;
            obj.origen = $scope.origen;
            obj.idcuenta = obj.objCuenta.id;
            obj.activada = 0;
            //console.log(obj); return;
            detContSrvc.editRow(obj, 'c').then(function(){
                $scope.rowFacturaExpanded({ id: obj.idorigen });
                $scope.detcont = {debe:0.00, haber:0.00};
            });
        };

        $scope.updDetCont = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalUpdDetCont.html',
                controller: 'ModalUpdDetContCtrl',
                resolve:{
                    detalle: function(){ return obj; },
                    idempresa: function(){return +$scope.reembolso.idempresa; }
                }
            });

            modalInstance.result.then(function(){
                $scope.rowFacturaExpanded({ id: obj.idorigen });
                $scope.detcont = {debe:0.00, haber:0.00};
            }, function(){
                $scope.rowFacturaExpanded({ id: obj.idorigen });
                $scope.detcont = {debe:0.00, haber:0.00};
            });
        };

        $scope.delDetCont = function(obj){
            //console.log(obj); return;
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?',
                title: 'Eliminar cuenta', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow({id: obj.id}, 'd').then(function(){
                    $scope.rowFacturaExpanded({ id: obj.idorigen });
                    $scope.detcont = {debe:0.00, haber:0.00};
                });
            });
        };

        $scope.rowFacturaExpanded = function(obj){
            $scope.getCompra({ id: obj.id }, null, true);
            detContSrvc.lstDetalleCont($scope.origen, obj.id).then(function(d){
                var indice = 0;
                for(var i = 0; i < $scope.compras.length; i++){
                    if(parseInt($scope.compras[i].id) == parseInt(obj.id)){
                        indice = i;
                        break;
                    }
                }
                //console.log(d);
                $scope.compras[indice].detcont = d;
                //console.log($scope.compras[indice]);
                $scope.detcont = {debe:0.00, haber:0.00};
            });
        };

        $scope.closeReembolso = function(obj){
            //console.log(obj);
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalCierreReembolso.html',
                controller: 'ModalCierreReembolsoCtrl',
                resolve:{
                    fecIni: function(){return obj.finicio}
                }
            });

            modalInstance.result.then(function(fechacierre){
                obj.ffinstr = moment(fechacierre).format('YYYY-MM-DD');                
                reembolsoSrvc.editRow(obj, 'cierre').then(function(){ 
                    $scope.getLstReembolsos();
                    $scope.getReembolso(obj.id);
                });
            }, function(){ return 0; });
        };

        $scope.genChequeReembolso = function(obj){
            //console.log(obj);
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalGenChequeReembolso.html',
                controller: 'ModalGenChequeReembolsoCtrl',
                resolve:{
                    lsttipotrans: function(){ return $scope.tiposmov; },
                    lstbancos: function(){ return $scope.bancos; }
                }
            });

            modalInstance.result.then(function(seleccionados){
                obj.objBanco = seleccionados[0];
                obj.tipotrans = seleccionados[1].abreviatura;
                obj.numero = seleccionados[2].numero;
                obj.fechatrans = seleccionados[2].fechatrans;                
                reembolsoSrvc.editRow(obj, 'gentranban').then(function(){ 
                    $scope.getLstReembolsos();
                    $scope.getReembolso(obj.id); 
                });
            }, function(){ return 0; });
        };

        $scope.reabrirCC = function(obj){
            obj.userid = $scope.uid;
             $confirm({text: '¿Seguro(a) de abrir nuevamente?', title: 'Reabrir CC/REE No. ' + $filter('padNumber')(+obj.id, 5), ok: 'Sí', cancel: 'No'}).then(function() {
                reembolsoSrvc.reaperturar(obj).then(function(){ 
                    $scope.getLstReembolsos(); 
                    $scope.getReembolso(+obj.id);
                });
            });            
        };

        $scope.goToTranBan = function(idtranban, origen){
			//console.log('IdTranBan = ' + idtranban + ' - Origen = ' + origen); 
			//return;
            if(origen == 1){
                localStorageSrvc.set('idtranban', idtranban);
                $location.path('tranbanc');
            }            
        };

        $scope.printVersion = function(){
            reembolsoSrvc.toPrint($scope.reembolso.id).then(function(d){
                $scope.dataToPrint = d;
                $scope.total = {debe: 0.00, haber: 0.00};
                var tmp = [];
                for(var i = 0; i < d.length; i++){
                    tmp = d[i].detcont;
                    for(var j = 0; j < tmp.length; j++){
                        if(tmp[j].desccuentacont.toUpperCase().indexOf('TOTAL --->') == -1){
                            $scope.total.debe += parseFloat(tmp[j].debe);
                            $scope.total.haber += parseFloat(tmp[j].haber);
                        }
                    }
                }
                $scope.total.debe = parseFloat($scope.total.debe.toFixed(2));
                $scope.total.haber = parseFloat($scope.total.haber.toFixed(2));
                PrintElem('#toPrint', 'Reembolso');
            });
        };

        $scope.printPendientes = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalPrintPendientes.html',
                controller: 'ModalPrintPendintesCtrl',
                windowClass: 'app-modal-window',
                resolve:{ idempresa: function(){ return +$scope.reembolso.idempresa; } }
            });

            modalInstance.result.then(function(){ return 0; }, function(){ return 0; });
        };

        $scope.comprasColDef = [
            {
                columnHeaderDisplayName: 'No.',
                template: '<div ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}" style="text-align: center; font-weight: bold;">{{item.correlativo | number:0}}</div>'
            },
            {
                columnHeaderDisplayName: 'Tipo',
                template: '<div ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.tipofactura}}</div>',
                sortKey: 'tipofactura'
            },
            {
                columnHeaderDisplayName: 'Proveedor',
                template: '<div ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.proveedor}}</div>',
                sortKey: 'proveedor'
            },
            {
                columnHeaderDisplayName: 'N.I.T.',
                template: '<div class="text-right" ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.nit}}</div>',
                sortKey: 'nit'
            },
            {
                columnHeaderDisplayName: 'Documento',
                template: '<div class="text-right" ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.serie}}&nbsp;{{item.documento}}</div>',
                sortKey: 'documento'
            },
            {
                columnHeaderDisplayName: 'Fecha de factura',
                template: '<div ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.fechafactura | date:"dd/MM/yyyy"}}</div>',
                sortKey: 'fechaingreso'
            },
            {
                columnHeaderDisplayName: 'Tipo de compra',
                template: '<div ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.tipocompra}}</div>',
                sortKey: 'tipocompra'
            },
            {
                columnHeaderDisplayName: 'Total',
                template: '<div class="text-right" ng-class="{' + "'compra-pendiente':+item.revisada == 0, 'compra-revisada':+item.revisada == 1" + '}">{{item.simbolo}}&nbsp;{{item.totfact | number:2}}</div>',
                sortKey: 'totfact'
            }

        ];
    }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    reembolsoctrl.controller('ModalCierreReembolsoCtrl', ['$scope', '$uibModalInstance', 'toaster', 'fecIni', function($scope, $uibModalInstance, toaster, fecIni){
        $scope.fcierre = moment().toDate();

        $scope.ok = function () {
            if(moment($scope.fcierre).isValid() && moment($scope.fcierre).isAfter(fecIni)){
                $uibModalInstance.close($scope.fcierre);
            }else{
                toaster.pop({ type: 'error', title: 'Error en la fecha de cierre.', body: 'Favor seleccionar una fecha de cierre válida', timeout: 7000 });
            }
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    reembolsoctrl.controller('ModalGenChequeReembolsoCtrl', ['$scope', '$uibModalInstance', 'toaster', 'bancoSrvc', 'lsttipotrans', 'lstbancos', function($scope, $uibModalInstance, toaster, bancoSrvc, lsttipotrans, lstbancos){
        $scope.tipostrans = lsttipotrans;
        $scope.bancos = lstbancos;
        $scope.numerotranban = 0;
        $scope.fechatrans = moment().toDate();
        $scope.selectedBanco = {};
        $scope.selectedTipoMov = {};

        $scope.resetParams = function(){
            $scope.selectedTipoMov = {};
            $scope.numerotranban = 0;
            $scope.fechatrans = moment().toDate();
        };

        $scope.getNumCheque = function(tipoTran){
            if($scope.selectedBanco.id != null && $scope.selectedBanco.id != undefined){
                if($scope.selectedTipoMov.abreviatura === 'C'){
                    bancoSrvc.getCorrelativoBco(parseInt($scope.selectedBanco.id)).then(function(c){ $scope.numerotranban = parseInt(c[0].correlativo)});
                    $scope.revisaExistencia();
                }else{
                    $scope.numerotranban = 0;
                }
            }
        };

        $scope.revisaExistencia = function(){
            if($scope.selectedBanco.id != null && $scope.selectedBanco.id != undefined){
                if($scope.selectedTipoMov.abreviatura != null && $scope.selectedTipoMov.abreviatura != undefined){
                    bancoSrvc.checkTranExists(parseInt($scope.selectedBanco.id), $scope.selectedTipoMov.abreviatura, $scope.numerotranban).then(function(e){
                        var existe = parseInt(e.existe) == 1;
                        if(existe){
                            toaster.pop({ type: 'error', title: 'Cheque existente.',
                                body: 'La transacción No. ' + $scope.numerotranban + ' ya existe en el banco ' + $scope.selectedBanco.bancomoneda + '.',
                                timeout: 7000
                            });
                            $scope.numerotranban = 0;
                        }
                    });
                }else{
                    $scope.numerotranban = 0;
                }
            }
        };

        $scope.ok = function () {
            $scope.seleccionados = [];
            $scope.seleccionados.push($scope.selectedBanco);
            $scope.seleccionados.push($scope.selectedTipoMov);
            $scope.seleccionados.push({ numero: parseInt($scope.numerotranban), fechatrans: moment($scope.fechatrans).format('YYYY-MM-DD') });

            if($scope.selectedTipoMov.abreviatura == 'B'){
                if(parseInt($scope.numerotranban) > 0){
                    $uibModalInstance.close($scope.seleccionados);
                }else{
                    toaster.pop({ type: 'error', title: 'Datos insuficientes.', body: 'Favor ingresar el número de la nota de débito.', timeout: 7000 });
                }
            }else{
                $uibModalInstance.close($scope.seleccionados);
            }
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    reembolsoctrl.controller('ModalCtasGastoProvReemCtrl', ['$scope', '$uibModalInstance', 'lstctasgasto', function($scope, $uibModalInstance, lstctasgasto){
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
    reembolsoctrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm){
        //$scope.detcont = detalle;
        $scope.cuentas = [];

        //console.log($scope.detcont);

        cuentacSrvc.getByTipo(idempresa, 0).then(function(d){
            $scope.cuentas = d;
            $scope.detcont = detalle;
        });

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
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    reembolsoctrl.controller('ModalPrintPendintesCtrl', ['$scope', '$uibModalInstance', 'jsReportSrvc', 'idempresa', function($scope, $uibModalInstance, jsReportSrvc, idempresa){
        $scope.params = {fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), idempresa: idempresa};

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

        var test = false;
        $scope.genReporte = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            jsReportSrvc.getPDFReport(test ? 'rkiDet5SW' : 'HyrzNi9rZ', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.genReporte();

    }]);


}());
