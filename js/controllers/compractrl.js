(function(){

    var compractrl = angular.module('cpm.compractrl', ['cpm.comprasrvc']);

    compractrl.controller('compraCtrl', ['$scope', '$filter', 'compraSrvc', 'authSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'proveedorSrvc', 'tipoCompraSrvc', 'toaster', 'cuentacSrvc', 'detContSrvc', '$uibModal', '$confirm', 'monedaSrvc', 'tipoFacturaSrvc', 'tipoCombustibleSrvc', 'presupuestoSrvc', 'proyectoSrvc', 'jsReportSrvc', '$sce', '$window', function($scope, $filter, compraSrvc, authSrvc, empresaSrvc, DTOptionsBuilder, proveedorSrvc, tipoCompraSrvc, toaster, cuentacSrvc, detContSrvc, $uibModal, $confirm, monedaSrvc, tipoFacturaSrvc, tipoCombustibleSrvc, presupuestoSrvc, proyectoSrvc, jsReportSrvc, sce, $window){

        $scope.lasEmpresas = [];
        $scope.lasCompras = [];
        var hoy = new Date();
        $scope.laCompra = {galones: 0.00, idp: 0.00, ordentrabajo: undefined};
        $scope.editando = false;
        $scope.losProvs = [];
        $scope.losTiposCompra = [];
        $scope.losDetCont = [];
        $scope.elDetCont = {debe: 0.0, haber: 0.0};
        $scope.lasCtasMov = [];
        $scope.origen = 2;
        $scope.ctasGastoProv = [];
        $scope.yaPagada = false;
        $scope.tranpago = [];
        $scope.monedas = [];
        $scope.dectc = 2;
        $scope.lsttiposfact = [];
        $scope.combustibles = [];
        $scope.facturastr = '';
        $scope.fltrcomp = {fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate()};
        $scope.proyectos = [];
		$scope.params = {idcompra: 0};
        $scope.lstproyectoscompra = [];
        $scope.proyectocompra = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('fnRowCallback', rowCallback);

        empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; });
        tipoFacturaSrvc.lstTiposFactura().then(function(d){
            for(var i = 0; i < d.length; i++){ d[i].id = parseInt(d[i].id); d[i].paracompra = parseInt(d[i].paracompra); }
            $scope.lsttiposfact = d;
        });
        tipoCombustibleSrvc.lstTiposCombustible().then(function(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].impuesto = parseFloat(parseFloat(d[i].impuesto).toFixed(2));
            }
            $scope.combustibles = d;
        });

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.laCompra.objEmpresa = d[0];
                    $scope.dectc = parseInt(d[0].dectc);
                    monedaSrvc.lstMonedas().then(function(l){
                        $scope.monedas = l;
                        proyectoSrvc.lstProyectosPorEmpresa(+$scope.laCompra.objEmpresa.id).then(function(d){ 
                            $scope.proyectos = d; 
                            $scope.resetCompra();
                        });                        
                    });                    
                });
            }
        });

        proveedorSrvc.lstProveedores().then(function(d){ $scope.losProvs = d; });
        tipoCompraSrvc.lstTiposCompra().then(function(d){
            for(var i = 0; i < d.length; i++){ d[i].id = parseInt(d[i].id); }
            $scope.losTiposCompra = d;
        });

        presupuestoSrvc.presupuestosAprobados().then(function(d){ $scope.ots = d; });

        $scope.resetCompra = function(){
            $scope.laCompra = {
                fechaingreso: new Date(), mesiva: hoy.getMonth() + 1, fechafactura: new Date(), creditofiscal: 0, extraordinario: 0, noafecto: 0.0,
                objEmpresa: $scope.laCompra.objEmpresa, objMoneda: {}, tipocambio: 1, isr: 0.00, galones: 0.00, idp: 0.00, objTipoCombustible: {},
                totfact: 0.00, subtotal: 0.00, iva: 0.00, ordentrabajo: undefined, idproyecto: undefined
            };
            $scope.search = "";
            $scope.facturastr = '';
            $scope.losDetCont = [];
            $scope.tranpago = [];
            $scope.yaPagada = false;
            $scope.editando = false;
            monedaSrvc.getMoneda(parseInt($scope.laCompra.objEmpresa.idmoneda)).then(function(m){
                $scope.laCompra.objMoneda = m[0];
                $scope.laCompra.tipocambio = parseFloat(m[0].tipocambio).toFixed($scope.dectc);
            });
            goTop();
        };

        $scope.chkFecha = function(qFecha, cual){
            if(qFecha != null && qFecha != undefined){
                switch(cual){
                    case 1:
                        if($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined){
                            $scope.laCompra.fechapago = moment(qFecha).add(parseInt($scope.laCompra.objProveedor.diascred), 'days').toDate();
                        }
                        break;
                }
            }
        };

        $scope.setMesIva = function(fing){
            if(fing != null && fing != undefined){
                $scope.laCompra.mesiva = (moment(fing).month() + 1);
            }else{
                $scope.laCompra.mesiva = undefined;
            }
        };

        $scope.chkExisteCompra = function(){
            //console.log($scope.laCompra); return;
            var params = { idproveedor: 0, nit: '', serie: '', documento: 0 };
            if($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined){
                params.idproveedor = +$scope.laCompra.objProveedor.id;
                params.nit = $scope.laCompra.objProveedor.nit != null && $scope.laCompra.objProveedor.nit != undefined ? $scope.laCompra.objProveedor.nit.trim() : '';
            }

            if($scope.laCompra.serie != null && $scope.laCompra.serie != undefined){ params.serie = $scope.laCompra.serie.trim(); }

            if($scope.laCompra.documento != null && $scope.laCompra.documento != undefined){ params.documento = +$scope.laCompra.documento; }

            if(params.documento > 0){
                compraSrvc.existeCompra(params).then(function(d){
                    if(+d.existe == 1){
                        var mensaje = 'La factura ' + d.serie + '-' + d.documento + ' del proveedor ' + d.proveedor + ' (' + d.nit + ') ' ;
                        mensaje += 'ya existe en la empresa ' + d.empresa + ' (' + d.abreviaempresa + '). Favor revisar.';
                        toaster.pop({ type: 'error', title: 'Esta factura ya existe',
                            body: mensaje, timeout: 9000 });                    
                    }
                });
            }            
        };

        function esCombustible(){
            if($scope.laCompra.objTipoCompra != null && $scope.laCompra.objTipoCompra != undefined){
                if($scope.laCompra.objTipoCompra.id != null && $scope.laCompra.objTipoCompra.id != undefined){
                    if(parseInt($scope.laCompra.objTipoCompra.id) == 3){
                        return true;
                    }
                }

            }
            return false;
        }

        function calcIDP(genidp){
            if(genidp && $scope.laCompra.objTipoCombustible != null && $scope.laCompra.objTipoCombustible != undefined){               
                var galones = $scope.laCompra.galones != null && $scope.laCompra.galones != undefined ? parseFloat($scope.laCompra.galones) : 0.00;
                var impuesto = $scope.laCompra.objTipoCombustible.impuesto != null && $scope.laCompra.objTipoCombustible.impuesto != undefined ? parseFloat($scope.laCompra.objTipoCombustible.impuesto) : 0.00;
                //console.log(galones); console.log(impuesto); console.log((galones * impuesto).toFixed(2));
                return (galones * impuesto).toFixed(2);
            }
            return 0.00;
        }

        $scope.calcular = function(){
            var geniva = true;
            var genidp = esCombustible();
            var totFact = $scope.laCompra.totfact != null && $scope.laCompra.totfact != undefined ? parseFloat($scope.laCompra.totfact) : 0;
            var noAfecto = $scope.laCompra.noafecto != null && $scope.laCompra.noafecto != undefined ? parseFloat($scope.laCompra.noafecto) : 0;
            var exento = 0.00, subtotal = 0.00;

            if($scope.laCompra.objTipoFactura != null && $scope.laCompra.objTipoFactura != undefined){ geniva = parseInt($scope.laCompra.objTipoFactura.generaiva) === 1; }
            //if($scope.laCompra.objTipoCompra != null && $scope.laCompra.objTipoCompra != undefined){ genidp = $scope.laCompra.objTipoCompra.id === 3; }

            $scope.laCompra.idp = calcIDP(genidp);

            //$scope.laCompra.idp = $scope.laCompra.idp.toFixed(2);
            /*
                exento = idp + noafecto + isr;
                subtotal = parseFloat((total - exento).toFixed(2));
                $scope.compra.subtotal = parseFloat(subtotal / 1.12).toFixed(2);
                $scope.compra.iva = parseFloat($scope.compra.subtotal * 0.12).toFixed(2);
            */

            exento = parseFloat($scope.laCompra.idp) + noAfecto;
            subtotal = totFact - exento;

            if(noAfecto <= totFact){
                $scope.laCompra.subtotal = geniva ? parseFloat(subtotal / 1.12).toFixed(2) : totFact;
                $scope.laCompra.iva = geniva ? parseFloat($scope.laCompra.subtotal * 0.12).toFixed(2) : 0.00;
            }else{
                $scope.laCompra.noafecto = 0;
                toaster.pop({ type: 'error', title: 'Error en el monto de No afecto.',
                    body: 'El monto de No afecto no puede ser mayor al total de la factura.', timeout: 7000 });
            }
        };

        $scope.$watch('laCompra.objEmpresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.getLstCompras();
            }
        });

        $scope.getConcepto = function(qProv){
            $scope.chkExisteCompra();
            if(!$scope.laCompra.id > 0){
                if(qProv != null && qProv != undefined){
                    $scope.laCompra.conceptomayor = qProv.concepto;
                    $scope.laCompra.fechapago = moment($scope.laCompra.fechaingreso).add(parseInt(qProv.diascred), 'days').toDate();
                    $scope.laCompra.objMoneda = $filter('getById')($scope.monedas, parseInt(qProv.idmoneda));
                    $scope.laCompra.tipocambio = parseFloat(qProv.tipocambioprov).toFixed($scope.dectc);
                }
            }
        };

        $scope.setTipoCambio = function(qmoneda){
            if($scope.laCompra.id > 0 || ($scope.laCompra.objProveedor != null && $scope.laCompra.objProveedor != undefined)){
                if(parseInt(qmoneda.id) === parseInt($scope.laCompra.objProveedor.idmoneda)){
                    $scope.laCompra.tipocambio = parseFloat($scope.laCompra.objProveedor.tipocambioprov);
                }else{
                    $scope.laCompra.tipocambio = parseFloat(qmoneda.tipocambio).toFixed($scope.dectc);
                }
            }else{ $scope.laCompra.tipocambio = parseFloat(qmoneda.tipocambio).toFixed($scope.dectc); }
        };

        function dateToStr(fecha){ return fecha !== null && fecha !== undefined ? (fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate()) : ''; }

        function procDataCompras(data){
            for(var i = 0; i < data.length; i++){
                data[i].documento = parseInt(data[i].documento);
                data[i].mesiva = parseInt(data[i].mesiva);
                data[i].totfact = parseFloat(parseFloat(data[i].totfact).toFixed(2));
                data[i].noafecto = parseFloat(parseFloat(data[i].noafecto).toFixed(2));
                data[i].subtotal = parseFloat(parseFloat(data[i].subtotal).toFixed(2));
                data[i].iva = parseFloat(parseFloat(data[i].iva).toFixed(2));
                data[i].isr = parseFloat(parseFloat(data[i].isr).toFixed(2));
                data[i].fechaingreso = moment(data[i].fechaingreso).toDate();
                data[i].fechafactura = moment(data[i].fechafactura).toDate();
                data[i].fechapago = moment(data[i].fechapago).toDate();
                data[i].creditofiscal = parseInt(data[i].creditofiscal);
                data[i].extraordinario = parseInt(data[i].extraordinario);
                data[i].idproveedor = parseInt(data[i].idproveedor);
                data[i].idtipocompra = parseInt(data[i].idtipocompra);
                data[i].cantpagos = parseInt(data[i].cantpagos);
                data[i].idmoneda = parseInt(data[i].idmoneda);
                data[i].tipocambio = parseFloat(parseFloat(data[i].tipocambio).toFixed($scope.dectc));
                data[i].idtipofactura = parseInt(data[i].idtipofactura);
                data[i].idtipocombustible = parseInt(data[i].idtipocombustible);
                data[i].galones = parseFloat(parseFloat(data[i].galones).toFixed(2));
                data[i].galones = parseFloat(parseFloat(data[i].galones).toFixed(2));
                data[i].idp = parseFloat(parseFloat(data[i].idp).toFixed(2));
                data[i].fecpagoformisr = moment(data[i].fecpagoformisr).isValid() ? moment(data[i].fecpagoformisr).toDate() : null;
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

        $scope.getLstCompras = function(){
            $scope.fltrcomp.fdelstr = moment($scope.fltrcomp.fdel).format('YYYY-MM-DD');
            $scope.fltrcomp.falstr = moment($scope.fltrcomp.fal).format('YYYY-MM-DD');
            $scope.fltrcomp.idempresa = +$scope.laCompra.objEmpresa.id;
            compraSrvc.lstComprasFltr($scope.fltrcomp).then(function(d){
                $scope.lasCompras = procDataCompras(d);
            });
        };

        $scope.getDetCont = function(idcomp){
            detContSrvc.lstDetalleCont($scope.origen, parseInt(idcomp)).then(function(detc){ $scope.losDetCont = procDataDet(detc); });
        };

        $scope.modalISR = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalISR.html',
                controller: 'ModalISR',
                resolve:{
                    compra: function(){
                        return $scope.laCompra;
                    }
                }
            });

            modalInstance.result.then(function(idcompra){
                $scope.getCompra(parseInt(idcompra));
            }, function(){ return 0; });
        };

        function formatoNumero(numero, decimales){ return $filter('number')(numero, decimales); }

        $scope.getCompra = function(idcomp){
            $scope.losDetCont = [];
            $scope.elDetCont = {debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined};
            compraSrvc.getCompra(idcomp).then(function(d){
                $scope.laCompra = procDataCompras(d)[0];
                $scope.laCompra.objProveedor = $filter('getById')($scope.losProvs, $scope.laCompra.idproveedor);
                $scope.laCompra.objMoneda = $filter('getById')($scope.monedas, $scope.laCompra.idmoneda);
                $scope.laCompra.objTipoFactura = $filter('getById')($scope.lsttiposfact, $scope.laCompra.idtipofactura);
                $scope.laCompra.objTipoCombustible = $filter('getById')($scope.combustibles, $scope.laCompra.idtipocombustible);
                $scope.search = $scope.laCompra.objProveedor.nitnombre;
                tipoCompraSrvc.getTipoCompra($scope.laCompra.idtipocompra).then(function(tc){ $scope.laCompra.objTipoCompra = tc[0]; });
                $scope.editando = true;
                cuentacSrvc.getByTipo($scope.laCompra.idempresa, 0).then(function(d){ $scope.lasCtasMov = d; });
                $scope.getDetCont(idcomp);
                $scope.loadProyectosCompra(idcomp);
                $scope.resetProyectoCompra();
                empresaSrvc.getEmpresa(parseInt($scope.laCompra.idempresa)).then(function(d){ $scope.laCompra.objEmpresa = d[0]; });
                compraSrvc.getTransPago(idcomp).then(function(d){
                    for(var i = 0; i < d.length; i++){
                        d[i].idtranban = parseInt(d[i].idtranban);
                        d[i].numero = parseInt(d[i].numero);
                        d[i].monto = parseFloat(d[i].monto);
                    }
                    $scope.tranpago = d;
                    $scope.yaPagada = $scope.tranpago.length > 0;
                });

                if($scope.laCompra.isr > 0){
                    if($scope.laCompra.noformisr == '' || $scope.laCompra.noformisr == undefined || $scope.laCompra.noformisr == null){
                        $scope.modalISR();
                    }
                }
                var tmp = $scope.laCompra, coma = ', ';

                $scope.facturastr = tmp.nomproveedor + coma + tmp.siglas + '-' + tmp.serie + '-' + tmp.documento + coma + moment(tmp.fechafactura).format('DD/MM/YYYY') + coma + tmp.desctipocompra + coma + 'Total: ' + tmp.moneda + ' ';
                $scope.facturastr+= formatoNumero(tmp.totfact, 2) + coma + 'No afecto: ' + tmp.moneda + ' ' + formatoNumero(tmp.noafecto, 2) + coma + ' Subtotal: ' + tmp.moneda + ' ' + formatoNumero(tmp.subtotal, 2) + coma;
                $scope.facturastr+= 'I.V.A.: ' + tmp.moneda + ' ' + formatoNumero(tmp.iva, 2) + coma + 'I.S.R.: ' + tmp.moneda + ' ' + formatoNumero(tmp.isr, 2) + coma + 'I.D.P.: ' + tmp.moneda + ' ' + formatoNumero(tmp.idp, 2);

                goTop();
            });
        };

        function execCreate(obj) {
            compraSrvc.editRow(obj,'c').then(function(d){
                if(+d.lastid > 0){
                    $scope.getLstCompras();
                    $scope.getCompra(parseInt(d.lastid));
                }else{
                    toaster.pop({ type: 'error', title: 'Error en la creación de la factura.',
                        body: 'La factura de este proveedor no pudo ser creada. Favor verifique que los datos estén bien ingresados y que la factura de este proveedor no exista.', timeout: 9000 });
                }
            });
        }

        function execUpdate(obj) {
            //console.log(obj);
            compraSrvc.editRow(obj,'u').then(function(d){
                $scope.getLstCompras();
                $scope.getCompra(parseInt(d.lastid));
            });
        }

        $scope.openSelectCtaGastoProv = function(obj, op){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalSelectCtaGastoProv.html',
                controller: 'ModalCtasGastoProvCtrl',
                resolve:{
                    lstctasgasto: function(){
                        return $scope.ctasGastoProv;
                    }
                }
            });

            modalInstance.result.then(function(selectedItem){
                obj.ctagastoprov = selectedItem.idcuentac;
                switch (op){
                    case 'c': execCreate(obj); break;
                    case 'u': execUpdate(obj); break;
                }
            }, function(){ return 0; });
        };

        $scope.addCompra = function(obj){
            obj.idempresa = parseInt(obj.objEmpresa.id);
            obj.idproveedor = parseInt(obj.objProveedor.id);
            obj.conceptoprov = obj.objProveedor.concepto;
            obj.idtipocompra = parseInt(obj.objTipoCompra.id);
            obj.creditofiscal = obj.creditofiscal != null && obj.creditofiscal != undefined ? obj.creditofiscal : 0;
            obj.extraordinario = obj.extraordinario != null && obj.extraordinario != undefined ? obj.extraordinario : 0;
            obj.ordentrabajo = obj.ordentrabajo != null && obj.ordentrabajo != undefined ? obj.ordentrabajo : 0;
            obj.fechaingresostr = dateToStr(obj.fechaingreso);
            obj.fechafacturastr = dateToStr(obj.fechafactura);
            obj.fechapagostr = dateToStr(obj.fechapago);
            obj.idmoneda = parseInt(obj.objMoneda.id);
            obj.idtipofactura = parseInt(obj.objTipoFactura.id);
            obj.idtipocombustible = obj.objTipoCombustible != null && obj.objTipoCombustible != undefined ? (obj.objTipoCombustible.id != null && obj.objTipoCombustible.id != undefined ? obj.objTipoCombustible.id : 0) : 0;
            //obj.idtipocombustible = 0;
            //obj.idproyecto = 0;

            proveedorSrvc.getLstCuentasCont(obj.idproveedor, obj.idempresa).then(function(lstCtas){
                $scope.ctasGastoProv = lstCtas;
                switch(true){
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

        $scope.updCompra = function(obj){
            $confirm({text: 'Este proceso eliminará el detalle contable que ya se haya ingresado y se creará uno nuevo. ¿Seguro(a) de continuar?',
                title: 'Actualización de factura de compra', ok: 'Sí', cancel: 'No'}).then(function() {
                obj.idempresa = parseInt(obj.idempresa);
                obj.idproveedor = parseInt(obj.objProveedor.id);
                obj.conceptoprov = obj.objProveedor.concepto;
                obj.idtipocompra = parseInt(obj.objTipoCompra.id);
                obj.creditofiscal = obj.creditofiscal != null && obj.creditofiscal != undefined ? obj.creditofiscal : 0;
                obj.extraordinario = obj.extraordinario != null && obj.extraordinario != undefined ? obj.extraordinario : 0;
                obj.ordentrabajo = obj.ordentrabajo != null && obj.ordentrabajo != undefined ? obj.ordentrabajo : 0;
                obj.fechaingresostr = dateToStr(obj.fechaingreso);
                obj.fechafacturastr = dateToStr(obj.fechafactura);
                obj.fechapagostr = dateToStr(obj.fechapago);
                obj.idmoneda = parseInt(obj.objMoneda.id);
                obj.idtipofactura = parseInt(obj.objTipoFactura.id);
                obj.idtipocombustible = obj.objTipoCombustible != null && obj.objTipoCombustible != undefined ? (obj.objTipoCombustible.id != null && obj.objTipoCombustible.id != undefined ? obj.objTipoCombustible.id : 0) : 0;
                //obj.idtipocombustible = 0;
                //obj.idproyecto = 0;

                proveedorSrvc.getLstCuentasCont(obj.idproveedor, obj.idempresa).then(function(lstCtas){
                    $scope.ctasGastoProv = lstCtas;
                    switch(true){
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

        $scope.delCompra = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta factura de compra? (También se eliminará su detalle contable)',
                title: 'Eliminar factura de compra', ok: 'Sí', cancel: 'No'}).then(function() {
                compraSrvc.editRow({id:obj.id}, 'd').then(function(){
                    $scope.getLstCompras();
                    $scope.resetCompra();
                });
            });
        };

        $scope.loadProyectosCompra = function(idcompra){
            compraSrvc.lstProyectosCompra(+idcompra).then(function(d){
                $scope.lstproyectoscompra = d;
            });
        };

        $scope.resetProyectoCompra = function(){
            $scope.proyectocompra = {
                id: 0, idcompra: $scope.laCompra.id, idproyecto: undefined, idcuentac: undefined, monto: null
            }
        };

        $scope.getProyectoCompra = function(idproycompra){
            compraSrvc.getProyectoCompra(+idproycompra).then(function(d){
                $scope.proyectocompra = d[0];
            });
        };

        $scope.addProyectoCompra = function(obj){
            compraSrvc.editRow(obj, 'cd').then(function(d){
                $scope.loadProyectosCompra(obj.idcompra);
                $scope.getProyectoCompra(d.lastid);
            });
        };

        $scope.updProyectoCompra = function(obj){
            compraSrvc.editRow(obj, 'ud').then(function(){
                $scope.loadProyectosCompra(obj.idcompra);
                $scope.getProyectoCompra(obj.id);
            });
        };

        $scope.delProyectoCompra = function(obj){
            compraSrvc.editRow({id: obj.id}, 'dd').then(function(){
                $scope.loadProyectosCompra(obj.idcompra);
                $scope.resetProyectoCompra();
            });
        };

        $scope.zeroDebe = function(valor){ $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function(valor){ $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        $scope.loadDetaCont = function(){
            $scope.losDetCont = [];
            detContSrvc.lstDetalleCont($scope.origen, +$scope.laCompra.id).then(function(detc){
                $scope.losDetCont = procDataDet(detc);
                $scope.elDetCont = {debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined};
            });
        };

        $scope.addDetCont = function(obj){
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.laCompra.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta.id);
            detContSrvc.editRow(obj, 'c').then(function(){
                detContSrvc.lstDetalleCont($scope.origen, parseInt($scope.laCompra.id)).then(function(detc){
                    $scope.losDetCont = procDataDet(detc);
                    $scope.elDetCont = {debe: 0.0, haber: 0.0, objCuenta: undefined, idcuenta: undefined};
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
                    idempresa: function(){return +$scope.laCompra.idempresa; }
                }
            });

            modalInstance.result.then(function(){
                $scope.loadDetaCont();
            }, function(){ $scope.loadDetaCont(); });
        };

        $scope.delDetCont = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar esta cuenta?', title: 'Eliminar cuenta contable', ok: 'Sí', cancel: 'No'}).then(function() {
                detContSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.getDetCont(obj.idorigen); });
            });
        };

        //$scope.printVersion = function(){ PrintElem('#toPrint', 'Factura de compra'); };
		$scope.printVersion = function(obj){
			
			var test = false;
           
			jsReportSrvc.getPDFReport(test ? '' : 'Hyh6Ta31z', {idcompra:obj.id}).then(function(pdf){ $window.open(pdf); });


        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalCtasGastoProvCtrl', ['$scope', '$uibModalInstance', 'lstctasgasto', function($scope, $uibModalInstance, lstctasgasto){
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
    compractrl.controller('ModalISR', ['$scope', '$uibModalInstance', 'compra', 'compraSrvc', function($scope, $uibModalInstance, compra, compraSrvc){
        $scope.compra = compra;
        $scope.compra.isrlocal = parseFloat(($scope.compra.isr * $scope.compra.tipocambio).toFixed(2));
        //console.log($scope.compra);

        $scope.setMesAnio = function(){
            if(moment($scope.compra.fecpagoformisr).isValid()){
                $scope.compra.mesisr = moment($scope.compra.fecpagoformisr).month() + 1;
                $scope.compra.anioisr = moment($scope.compra.fecpagoformisr).year();
            }
        };

        $scope.ok = function () {
            $scope.compra.noformisr = $scope.compra.noformisr != null && $scope.compra.noformisr != undefined ? $scope.compra.noformisr : '';
            $scope.compra.noaccisr = $scope.compra.noaccisr != null && $scope.compra.noaccisr != undefined ? $scope.compra.noaccisr : '';
            $scope.compra.fecpagoformisrstr = moment($scope.compra.fecpagoformisr).isValid() ? moment($scope.compra.fecpagoformisr).format('YYYY-MM-DD') : '';
            $scope.compra.mesisr = $scope.compra.mesisr != null && $scope.compra.mesisr != undefined ? $scope.compra.mesisr : 0;
            $scope.compra.anioisr = $scope.compra.anioisr != null && $scope.compra.anioisr != undefined ? $scope.compra.anioisr : 0;
            compraSrvc.editRow($scope.compra, 'uisr').then(function(){ $uibModalInstance.close($scope.compra.id); });
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);
    //------------------------------------------------------------------------------------------------------------------------------------------------//
    compractrl.controller('ModalUpdDetContCtrl', ['$scope', '$uibModalInstance', 'detalle', 'cuentacSrvc', 'idempresa', 'detContSrvc', '$confirm', function($scope, $uibModalInstance, detalle, cuentacSrvc, idempresa, detContSrvc, $confirm){
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
