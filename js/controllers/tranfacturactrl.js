(function(){

    var facturacionctrl = angular.module('cpm.facturacionctrl', []);

    facturacionctrl.controller('facturacionCtrl', ['$scope', 'facturacionSrvc', 'facturacionAguaSrvc', 'facturaOtrosSrvc', 'authSrvc', 'empresaSrvc', 'tipoServicioVentaSrvc', '$filter', 'tipoCambioSrvc', 'jsReportSrvc', '$window', '$uibModal', 'toaster', 'clienteSrvc', 'tipoFacturaSrvc', 'tipoCompraSrvc', '$confirm', 'proyectoSrvc', 'factsParqueoSrvc', function($scope, facturacionSrvc, facturacionAguaSrvc, facturaOtrosSrvc, authSrvc, empresaSrvc, tipoServicioVentaSrvc, $filter, tipoCambioSrvc, jsReportSrvc, $window, $uibModal, toaster, clienteSrvc, tipoFacturaSrvc, tipoCompraSrvc, $confirm, proyectoSrvc, factsParqueoSrvc){

        $scope.params = { idempresa: '0', fvence: moment().endOf('month').toDate(), ffactura: moment().toDate(), idtipo: '0', tc: 1.00, objTipo: undefined, params:'', pedientes: [] };
        $scope.paramsh2o = { idempresa: '0', fvence: moment().endOf('month').toDate(), ffactura: moment().toDate(), tc: 1.00 };
        $scope.empresas = [];
        $scope.tipos = [];
        $scope.pendientes = [];
        $scope.pendientesh2o = [];
        $scope.showForm = {};
        $scope.showparams = true;
        $scope.showparamsh2o = true;
        $scope.paramsstr = '';
        $scope.empredefault = undefined;
        $scope.allnone = 1;
        $scope.suma = { cantidad: 0, totmonto: 0.00 };
        $scope.paramsParqueo = {idempresa: undefined, idproyecto: undefined, fdel: moment().toDate(), fal: moment().toDate(), tc: 1.00};

        authSrvc.getSession().then(function(usrLogged){
            empresaSrvc.lstEmpresas().then(function(d){
                $scope.empresas = d;
                $scope.params.idempresa = usrLogged.workingon.toString();
                $scope.paramsh2o.idempresa = usrLogged.workingon.toString();
                $scope.paramsParqueo.idempresa = usrLogged.workingon.toString();
                $scope.empredefault = usrLogged.workingon.toString();
                $scope.resetFactura();
            });
            $scope.usrdata = usrLogged;
        });

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){
            $scope.tipos = d;
            $scope.tipos.push({id: '0', desctiposervventa: 'Todos'});
            //$scope.params.objTipo = [$filter('getById')($scope.tipos, $scope.params.idtipo)];
        });

        tipoCambioSrvc.getTC().then(function(){
            tipoCambioSrvc.getLastTC().then(function(d){
                $scope.params.tc = parseFloat(parseFloat(d.lasttc).toFixed(2));
                $scope.paramsh2o.tc = parseFloat(parseFloat(d.lasttc).toFixed(2));
                $scope.paramsParqueo.tc = parseFloat(parseFloat(d.lasttc).toFixed(2));
            });
        });

        $scope.$on('epups', function(ngRepeatFinishedEvent){ enablePopOvers(); });

        function procesaPendientes(d){
            for(var i = 0; i < d.length; i++){
                d[i].idcontrato = parseInt(d[i].idcontrato);
                //eval('$scope.showForm.det_' + d[i].idcontrato + ' = false;');
                d[i].idcliente = parseInt(d[i].idcliente);
                d[i].idtipocliente = parseInt(d[i].idtipocliente);
                d[i].montosiniva = parseFloat(parseFloat(d[i].montosiniva).toFixed(2));
                d[i].montoconiva = parseFloat(parseFloat(d[i].montoconiva).toFixed(2));
                d[i].retisr = parseFloat(parseFloat(d[i].retisr).toFixed(2));
                d[i].retiva = parseInt(d[i].retiva);
                d[i].ivaaretener = parseFloat(parseFloat(d[i].ivaaretener).toFixed(2));
                d[i].totapagar = parseFloat(parseFloat(d[i].totapagar).toFixed(2));
                d[i].facturar = parseInt(d[i].facturar);
                for(var j = 0; j < d[i].detalle.length; j++){
                    d[i].detalle[j].id = parseInt(d[i].detalle[j].id);
                    d[i].detalle[j].idcontrato = parseInt(d[i].detalle[j].idcontrato);
                    d[i].detalle[j].mes = parseInt(d[i].detalle[j].mes);
                    d[i].detalle[j].anio = parseInt(d[i].detalle[j].anio);
                    d[i].detalle[j].facturar = parseInt(d[i].detalle[j].facturar);
                    d[i].detalle[j].montosiniva = parseFloat(parseFloat(d[i].detalle[j].montosiniva).toFixed(2));
                    d[i].detalle[j].montoconiva = parseFloat(parseFloat(d[i].detalle[j].montoconiva).toFixed(2));
                    d[i].detalle[j].montoflatconiva = parseFloat(parseFloat(d[i].detalle[j].montoflatconiva).toFixed(2));
                    d[i].detalle[j].descuento = parseFloat(parseFloat(d[i].detalle[j].descuento).toFixed(2));
                    d[i].detalle[j].montocargoflat = parseFloat(parseFloat(d[i].detalle[j].montocargoflat).toFixed(2));
                }
            }
            //console.log($scope.showForm);
            return d;
        }

        $scope.setFVence = function(){
            $scope.params.fvence = moment($scope.params.fvence).isValid ? moment($scope.params.fvence).endOf('month').toDate() : $scope.params.fvence;
        };

        $scope.getPendientes = function(){
            $scope.setFVence();

            if($scope.params.objTipo != null && $scope.params.objTipo != undefined){
                if($scope.params.objTipo.length <= 0) { $scope.params.objTipo.push({id: '0', desctiposervventa: 'Todos'}); }
            }else{
                $scope.params.objTipo = [{id: '0', desctiposervventa: 'Todos'}];
            }

            $scope.paramsstr  = ($filter('getById')($scope.empresas, $scope.params.idempresa)).nomempresa + ' - Vence: ' + moment($scope.params.fvence).format('DD/MM/YYYY') + ' - ';
            $scope.paramsstr += 'Fecha de factura: ' + moment($scope.params.ffactura).format('DD/MM/YYYY') + ' - TC: ' + $filter('number')($scope.params.tc, 2) + ' - ';
            $scope.paramsstr += 'Facturar: ' + objectPropsToList($scope.params.objTipo, 'desctiposervventa', ', ');

            $scope.params.idtipo = objectPropsToList($scope.params.objTipo, 'id', ',');
            $scope.params.idtipo = $scope.params.idtipo != '0' ? $scope.params.idtipo : '';
            $scope.params.fvencestr = moment($scope.params.fvence).isValid() ? moment($scope.params.fvence).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            $scope.params.params = $scope.paramsstr;
            //console.log($scope.params); return;
            facturacionSrvc.lstCargosPendientes($scope.params).then(function(d){
                $scope.showForm = {};
                $scope.pendientes = procesaPendientes(d);
                //console.log($scope.pendientes);
                $scope.showparams = false;
                $scope.calculaMontos();
            });
        };

        $scope.toggleRowVisibility = function(idcontrato){ eval('$scope.showForm.det_' + idcontrato + ' = !$scope.showForm.det_' + idcontrato); };

        var test = false;

        $scope.printPreliminar = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalRptPreliminar.html',
                controller: 'ModalRptPreliminarCtrl',
                resolve:{
                    empresas: function(){ return $scope.empresas; }
                }
            });

            modalInstance.result.then(function(params){
                //console.log(params);
                jsReportSrvc.getPDFReport(test ? 'r1i2UWmag' : 'BkkmC1V6x', params).then(function(pdf){
                    $window.open(pdf);
                });
            }, function(){ return 0; });

        };

        $scope.printFactPreview = function(){
            var pendientes = [];

            $scope.pendientes.forEach(function(p){
                if(+p.facturar == 1){
                    //console.log(p);
                    pendientes.push({
                        cliente: p.cliente, descuento: p.descuento, detalle: [], facturar: p.facturar, facturara: p.facturara, idcliente: p.idcliente, idcontrato: p.idcontrato, idtipocliente: p.idtipocliente, ivaaretener: p.ivaaretener,
                        montoconiva: p.montoconiva, montosiniva: p.montosiniva, numfact: p.numfact, paramstr: p.paramstr, proyecto: p.proyecto, retisr: p.retisr, retiva: p.retiva, seriefact: p.seriefact, tipo: p.tipo, totapagar: p.totapagar,
                        unidades: p.unidades
                    });
                    //console.log(pendientes);
                    p.detalle.forEach(function(d){
                        if(+d.facturar == 1){
                            pendientes[pendientes.length - 1].detalle.push(d);
                        }
                    });
                }
            });

            //console.log(pendientes); return;
            $scope.params.pendientes = pendientes;

            jsReportSrvc.getPDFReport(test ? 'S1snRV0ye' : 'ryNPcSCJg', $scope.params).then(function(pdf){
                $window.open(pdf);
            });
        };

        $scope.verDetFact = function(obj){
            //console.log(obj);
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalDetalleFactura.html',
                controller: 'ModalDetFactCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    item: function(){ return obj; },
                    usr: function() { return $scope.usrdata; },
                    idempresa: function(){return $scope.params.idempresa; }
                }
            });

            modalInstance.result.then(function(item){
                $scope.updateFacturacion(item);
            }, function(){ return 0; });
        };

        $scope.updateFacturacion = function(obj){ enablePopOvers(); $scope.calculaMontos(); };

        $scope.marcarDesmarcar = function(){
            $scope.pendientes.forEach(function(pendiente){
                pendiente.facturar = $scope.allnone;
            });
            $scope.calculaMontos();
        };

        $scope.calculaMontos = function(){
            $scope.suma = { cantidad: 0, totmonto: 0.00 };
            $scope.pendientes.forEach(function(pendiente){
                if(pendiente.facturar == 1){
                    $scope.suma.cantidad += 1;
                    $scope.suma.totmonto += parseFloat(pendiente.montoconiva);
                }
            });
        };

        $scope.factSelected = function(){
            $scope.params.ffacturastr = moment($scope.params.ffactura).isValid() ? moment($scope.params.ffactura).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            var aFact = {params: $scope.params, pendientes: []};
            $scope.pendientes.forEach(function(pendiente){
                if(+pendiente.facturar == 1){
                    aFact.pendientes.push(pendiente);
                }
            });
            //console.log(aFact); return;
            if(aFact.pendientes.length > 0){
                facturacionSrvc.generarFacturas(aFact).then(function(){
                    $scope.getPendientes();
                    toaster.pop('info', 'Facturación', 'Facturas generadas...');
                });
            }
        };

        //--------------------------- Facturación de servicio de agua ------------------------------------------------------------------------------------------------------------------------//
        $scope.getPendientesH2O = function(){
            $scope.paramsh2o.fvencestr = moment($scope.params.fvence).isValid() ? moment($scope.paramsh2o.fvence).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            facturacionAguaSrvc.lstCargosPendientes($scope.paramsh2o).then(function(d){
                $scope.pendientesh2o = d;
            });
        };

        $scope.factSelectedH2O = function(){
            $scope.paramsh2o.ffacturastr = moment($scope.paramsh2o.ffactura).isValid() ? moment($scope.paramsh2o.ffactura).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            var aFact = {params: $scope.paramsh2o, pendientes: []};
            $scope.pendientesh2o.forEach(function(pendiente){
                if(+pendiente.facturar == 1){
                    aFact.pendientes.push(pendiente);
                }
            });
            if(aFact.pendientes.length > 0){
                facturacionAguaSrvc.generarFacturas(aFact).then(function(){
                    toaster.pop('info', 'Facturación', 'Facturas de agua generadas...');
                    $scope.getPendientesH2O();
                });
            }
        };

        $scope.printPreliminarAgua = function(){
            $scope.paramsh2o.fvencestr = moment($scope.params.fvence).isValid() ? moment($scope.paramsh2o.fvence).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            $scope.paramsh2o.empresastr = ($filter('getById')($scope.empresas, +$scope.paramsh2o.idempresa)).nomempresa;
            jsReportSrvc.getPDFReport(test ? 'ryPLSZ4Tg' : 'H1giYZETg', $scope.paramsh2o).then(function(pdf){
                $window.open(pdf);
            });
        };

        //--------------------------- Facturación de otros servicios (facturas insertadas) -----------------------------------------------------------------------------------------------------//

        $scope.factura = {
            id: 0, fechaingreso: moment().toDate(), fecha: moment().toDate(), idtipoventa: '2', idmoneda: 1, tipocambio: null, conceptomayor: undefined, idempresa: $scope.empredefault,
            anioafecta: null, mesafecta: null, retenerisr: 0, reteneriva: 0, idproyecto: undefined, idcontrato: undefined, direccion: undefined
        };
        $scope.contratos = [];
        $scope.tiposfactura = [];
        $scope.tiposventa = [];
        $scope.facturas = [];
        $scope.detsfact = [];
        $scope.detfact = {};
        $scope.tsv = [];
        $scope.proyectos = [];

        tipoFacturaSrvc.lstTiposFactura().then(function(d){ $scope.tiposfactura = d; });
        tipoCompraSrvc.lstTiposCompra().then(function(d){ $scope.tiposventa = d; });
        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tsv = d; });

        $scope.errorSearch = function(error){
            //console.log(error);
        };

        $scope.$watch('factura.idempresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.setTipoFactura(newValue);
                $scope.loadProyectos(newValue);
            }
        });

        $scope.setTipoFactura = function(idempresa){
            for(var i = 0; i < $scope.empresas.length; i++){
                if(+$scope.empresas[i].id == +idempresa){
                    $scope.factura.idtipofactura = +$scope.empresas[i].congface == 1 ? '1': '2';
                    break;
                }
            }
        };

        $scope.loadProyectos = function(idempresa){
            proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then(function(d){ $scope.proyectos = d; });
        };

        $scope.setProyecto = function(item, model){
            $scope.proyectos.forEach(function(proy){
                if(+item.idproyecto == +proy.id){
                    $scope.factura.idproyecto = item.idproyecto;
                }
            });
        };

        $scope.clienteSelected = function(item){
            if(item != null && item != undefined){
                switch(typeof item.originalObject){
                    case 'string':
                        $scope.factura.nombre = item.originalObject;
                        $scope.factura.nit = undefined;
                        $scope.factura.idcliente = 0;
                        $scope.factura.idcontrato = 0;
                        $scope.factura.reteneriva = 0;
                        $scope.factura.retenerisr = 0;
                        $scope.factura.direccion = undefined;
                        $scope.contratos = [];
                        break;
                    case 'object':
                        $scope.factura.nombre = item.originalObject.facturara;
                        $scope.factura.nit = item.originalObject.nit;
                        if(+item.originalObject.idcliente > 0){
                            $scope.factura.idcliente = +item.originalObject.idcliente;
                            $scope.factura.retenerisr = +item.originalObject.retisr;
                            $scope.factura.reteneriva = +item.originalObject.retiva;
                            $scope.factura.direccion = item.originalObject.direccion;
                            clienteSrvc.lstContratosEmpresa(+item.originalObject.idcliente, +$scope.factura.idempresa).then(function(d){
                                $scope.contratos = d;
                            });
                        } else {
                            $scope.factura.idcontrato = 0;
                            $scope.factura.reteneriva = 0;
                            $scope.factura.retenerisr = 0;
                            $scope.factura.direccion = undefined;
                            $scope.contratos = [];
                        }
                        break;
                }
            }
        };

        function procDataFact(d){
            for(var i = 0; i < d.length; i++){
                d[i].fecha = moment(d[i].fecha).toDate();
                d[i].fechaingreso = moment(d[i].fechaingreso).toDate();
                d[i].iva = parseFloat(parseFloat(d[i].iva).toFixed(2));
                d[i].total = parseFloat(parseFloat(d[i].total).toFixed(2));
                d[i].noafecto = parseFloat(parseFloat(d[i].noafecto).toFixed(2));
                d[i].subtotal = parseFloat(parseFloat(d[i].subtotal).toFixed(2));
                d[i].retisr = parseFloat(parseFloat(d[i].retisr).toFixed(2));
                d[i].retiva = parseFloat(parseFloat(d[i].retiva).toFixed(2));
                d[i].totdescuento = parseFloat(parseFloat(d[i].totdescuento).toFixed(2));
                d[i].tipocambio = parseFloat(parseFloat(d[i].tipocambio).toFixed(2));
                d[i].retenerisr = parseInt(d[i].retenerisr);
                d[i].reteneriva = parseInt(d[i].reteneriva);
                d[i].anioafecta = parseInt(d[i].anioafecta);
                d[i].idproyecto = +d[i].idproyecto > 0 ? d[i].idproyecto : undefined;
            }
            return d;
        }

        $scope.loadFacturas = function(idempresa, cuales){
            facturaOtrosSrvc.lstFacturas(+idempresa, +cuales).then(function(d){ $scope.facturas = d; });
        };

        $scope.getFactura = function(idfactura){
            $scope.detsfact = [];
            $scope.detfact = {};
            facturaOtrosSrvc.getFactura(idfactura).then(function(d){
                // console.log($scope.factura);
                $scope.$broadcast('angucomplete-alt:changeInput', 'txtCliente', {nit: d[0].nit, facturara: d[0].nombre});
                if(+d[0].idcliente > 0){
                    clienteSrvc.lstContratos(+d[0].idcliente).then(function(d){ $scope.contratos = d; });
                }
                $scope.factura = procDataFact(d)[0];

                $scope.loadDetalleFactura(idfactura);
                $scope.resetDetalleFactura();
                moveToTab('divListaFacturacionOtros', 'divCreaEditaFacturasOtros');
                goTop();
            });
        };

        function setObjFact(obj){
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.fechaingresostr = moment(obj.fechaingreso).format('YYYY-MM-DD');
            obj.mesiva = moment(obj.fechaingreso).month() + 1;
            obj.idcontrato = obj.idcontrato != null && obj.idcontrato != undefined ? obj.idcontrato : 0;
            obj.idcliente = obj.idcliente != null && obj.idcliente != undefined ? obj.idcliente : 0;
            obj.serie = obj.serie != null && obj.serie != undefined ? obj.serie : '';
            obj.numero = obj.numero != null && obj.numero != undefined ? obj.numero : '';
            obj.reteneriva = obj.reteneriva != null && obj.reteneriva != undefined ? obj.reteneriva : 0;
            obj.retenerisr = obj.retenerisr != null && obj.retenerisr != undefined ? obj.retenerisr : 0;
            obj.direccion = obj.direccion != null && obj.direccion != undefined ? obj.direccion : '';
            obj.conceptomayor = obj.conceptomayor != null && obj.conceptomayor != undefined ? obj.conceptomayor : '';
            obj.idproyecto = obj.idproyecto != null && obj.idproyecto != undefined ? obj.idproyecto : 0;
            return obj;
        }

        $scope.resetFactura = function(){
            $scope.factura = {
                id: 0, fechaingreso: moment().toDate(), fecha: moment().toDate(), idtipoventa: '2', idmoneda: 1, tipocambio: null, conceptomayor: undefined,
                idempresa: undefined, anioafecta: null, mesafecta: null, retenerisr: 0, reteneriva: 0, direccion: undefined, idproyecto: undefined,
                idtipofactura: (+$scope.factura.idtipofactura > 0 ? $scope.factura.idtipofactura : undefined)
            };
            $scope.factura.idempresa = $scope.empredefault;
            $scope.$broadcast('angucomplete-alt:clearInput', 'txtCliente');
        };

        $scope.addFactura = function(obj){
            obj = setObjFact(obj);
            facturaOtrosSrvc.editRow(obj,'c').then(function(d){
                $scope.loadFacturas(0, 1);
                $scope.getFactura(+d.lastid);
            });
        };

        $scope.updFactura = function(obj){
            obj = setObjFact(obj);
            facturaOtrosSrvc.editRow(obj,'u').then(function(d){
                $scope.loadFacturas(0, 1);
                $scope.getFactura(obj.id);
            });
        };

        $scope.delFactura = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar la factura ' + obj.serie + ' ' + obj.numero + '?', title: 'Eliminar factura', ok: 'Sí', cancel: 'No'}).then(function() {
                facturaOtrosSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.loadFacturas(0, 1); $scope.resetFactura(); });
            });
        };

        $scope.resetDetalleFactura = function(){ $scope.detfact = { cantidad: 1, mes: moment($scope.factura.fecha).month + 1, anio: moment($scope.factura.fecha).year(), descripcion: '', descuento: 0.00 }; };

        $scope.loadDetalleFactura = function(idfactura){
            facturaOtrosSrvc.lstDetFactura(idfactura).then(function(d){
                $scope.detsfact = d;
            });
        };

        $scope.getDetalleFactura = function(iddetfact){
            facturaOtrosSrvc.getDetFactura(iddetfact).then(function(d){
                $scope.detfact = d[0];
            });
        };

        function setObjDetFact(obj){
            obj.idfactura = $scope.factura.id;
            //obj.mes = moment($scope.factura.fecha).month() + 1;
            obj.mes = $scope.factura.mesafecta;
            //obj.anio = moment($scope.factura.fecha).year();
            obj.anio = $scope.factura.anioafecta;
            obj.preciotot = parseFloat((+obj.cantidad * +obj.preciounitario).toFixed(2));
            obj.conceptomayor = obj.conceptomayor != null && obj.conceptomayor != undefined ? obj.conceptomayor : '';
            return obj;
        }

        $scope.addDetFact = function(obj){
            obj = setObjDetFact(obj);
            facturaOtrosSrvc.editRow(obj, 'cd').then(function(){
                $scope.getFactura(obj.idfactura);
            });
        };

        $scope.delDetFact = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar '+ obj.cantidad + ' ' + obj.tiposervicio + ' - ' + obj.descripcion + ' por Q. ' + $filter('number')(obj.preciotot, 2) + '?',
                title: 'Eliminar detalle de factura', ok: 'Sí', cancel: 'No'}).then(function() {
                facturaOtrosSrvc.editRow({id:obj.id, idfactura:obj.idfactura, idtiposervicio: obj.idtiposervicio}, 'dd').then(function(){ $scope.getFactura(obj.idfactura); });
            });
        };

        //--------------------------- Impresion de facturas preimpresas -----------------------------------------------------------------------------------------------------//
        $scope.paramsimp = { idempresa: $scope.factura.idempresa, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), formato: 'printfacturas' };
        $scope.lstimpfact = [];

        $scope.getLstImpFact = function(){
            $scope.paramsimp.fdelstr = moment($scope.paramsimp.fdel).format('YYYY-MM-DD');
            $scope.paramsimp.falstr = moment($scope.paramsimp.fal).format('YYYY-MM-DD');
            facturacionSrvc.lstImpresionFacturas($scope.paramsimp).then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].imprimir = parseInt(d[i].imprimir);
                }
                $scope.lstimpfact = d;
            });
        };

        $scope.formatoFactura = function(){
            for(var i = 0; i < $scope.empresas.length; i++){
                //console.log("FORMATO " + i + ": " + $scope.empresas[i].formatofactura);
                if(+$scope.empresas[i].id == +$scope.paramsimp.idempresa){
                    $scope.paramsimp.formato = $scope.empresas[i].formatofactura;
                    break;
                }else{
                    $scope.paramsimp.formato = 'printfacturas';
                }
            }
        };

        $scope.imprimirFactPreImp = function(){
            var lstids = '';
            for(var i = 0; i < $scope.lstimpfact.length; i++){
                //console.log($scope.lstimpfact[i].imprimir);
                if($scope.lstimpfact[i].imprimir == 1){
                    if(lstids != ''){ lstids += ','; }
                    lstids += $scope.lstimpfact[i].id;
                }
            }              
            var gadget = new cloudprint.Gadget();
            var url = window.location.origin + "/sayet/php/" + $scope.paramsimp.formato + ".php?idfacturas=" + lstids;
            console.log(url);
            gadget.setPrintDocument("url", "Facturas", url);
            gadget.openPrintDialog();
        };

        $scope.loadFacturas(0, 1);

        //--------------------------- Facturas de parqueo -----------------------------------------------------------------------------------------------------//
        $scope.proyectosParqueo = [];
        //$scope.paramsParqueo = {idempresa: undefined, idproyecto: undefined, fdel: moment().toDate(), fal: moment().toDate()};
        $scope.factsparqueo = [];
        $scope.generando = false;
        $scope.fechaactual = '';
        $scope.mensaje = '';

        $scope.$watch('paramsParqueo.idempresa', function(newValue, oldValue){
            if(newValue != null && newValue != undefined){
                $scope.loadProyectosParqueo();
            }
        });

        $scope.$watch('generando', function(newValue, oldValue){
            // console.log('Generando:', newValue);
        });

        $scope.loadProyectosParqueo = function(){
            proyectoSrvc.lstProyectosPorEmpresa(+$scope.paramsParqueo.idempresa).then(function(d){ $scope.proyectosParqueo = d; });
        };

        function getFacts(url){ return factsParqueoSrvc.getFacturas({url: url}).then(function(d){return d; }); }

        $scope.getFacturasParqueo = async function(){
            var url = $scope.proyectosParqueo[$scope.proyectosParqueo.map(function(p){ return p.id}).indexOf($scope.paramsParqueo.idproyecto)].apiurlparqueo;
            var fechaini = moment($scope.paramsParqueo.fdel), fechafin = moment($scope.paramsParqueo.fal), facturas = [];
            var texto = '';
            $scope.generando = true;
            while(fechaini <= fechafin){
                // console.log('Fecha:', fechaini.format('DD/MM/YYYY'));
                $scope.fechaactual = fechaini.format('DD/MM/YYYY');
                $scope.mensaje = 'Descargando facturas del ' + $scope.fechaactual + '.';
                var facts = await getFacts(url + fechaini.format('DD-MM-YYYY'));
                facturas = facturas.concat(facts);
                fechaini = fechaini.add(1, 'days');
            }

            if(facturas.length > 0){
                texto = 'Se van a insertar ' + $filter('number')(facturas.length, 0) + ' facturas de parqueo. ¿Seguro(a) de continuar?';
            } else {
                texto = 'Lo siento, no se encontraron facturas de parqueo en el rango seleccionado. Intente de nuevo, por favor.';
            }

            $confirm({text: texto, title: 'Facturas de parqueo', ok: 'Sí', cancel: 'No'}).then(function() {
                if(facturas.length > 0){
                    var obj = {
                        idempresa: $scope.paramsParqueo.idempresa,
                        idproyecto: $scope.paramsParqueo.idproyecto,
                        tc: $scope.paramsParqueo.tc,
                        facturas: facturas
                    };
                    $scope.mensaje = 'Insertando ' + $filter('number')(facturas.length, 0) + ' facturas de parqueo...';
                    factsParqueoSrvc.insertaFacturas(obj).then(function(d){
                        $scope.generando = false;
                        toaster.pop('info', 'Facturas de parqueo', $filter('number')(facturas.length, 0) + ' facturas de parqueo insertadas.');
                    });
                } else {
                    $scope.generando = false;
                    toaster.pop('info', 'No se insertó ninguna factura de parqueo.');
                }
            }, function(){
                $scope.generando = false;
                toaster.pop('info', 'No se insertó ninguna factura de parqueo.');
            });
        }

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    facturacionctrl.controller('ModalDetFactCtrl', ['$scope', '$uibModalInstance', 'toaster', 'facturacionSrvc', '$filter', 'item', 'usr', 'idempresa', function($scope, $uibModalInstance, toaster, facturacionSrvc, $filter, item, usr, idempresa){
        $scope.p = item;
        $scope.todos = 1;

        $scope.recalcular = function(obj){
            var msiniva = 0.00, tipo = '', cntAFact = 0, mconiva = 0.00, descuento = 0.00, montoflatconiva = 0.00, montocargoflat = 0.00;
            obj.detalle.forEach(function(item, index){
                if(+item.facturar === 1){
                    msiniva += item.montosiniva;
                    mconiva += item.montoconiva;
                    descuento += item.descuento;
                    montoflatconiva += item.montoflatconiva;
                    montocargoflat += item.montocargoflat;
                    if(tipo != ''){ tipo += ',<br/>'; }
                    tipo += item.tipo + ' ' + item.mes + '/' + item.anio;
                    cntAFact += 1;
                }else{ $scope.todos = 0; }
            });
            $scope.todos = cntAFact === obj.detalle.length ? 1 : 0;
            obj.montosiniva = msiniva;
            obj.montoconiva = mconiva;
            obj.iva = obj.montoconiva - obj.montosiniva;
            obj.descuento = descuento;
            obj.montocargoconiva = montoflatconiva;
            obj.montocargoflat = montocargoflat;
            obj.tipo = tipo;

            var param = {
                idempresa: idempresa, retisr: 0.00, montosiniva: obj.montosiniva, montoconiva: obj.montoconiva, retiva: obj.retiva, idtipocliente: obj.idtipocliente, retenerisr: obj.retenerisr, descuento: obj.descuento, iva: obj.iva
            };
            facturacionSrvc.recalcular(param).then(function(d){
                //console.log(d);
                obj.retisr = d.retisr;
                obj.ivaaretener = d.ivaaretener;
                obj.totapagar = d.totapagar;
            });
        };

        $scope.toggleFact = function(valor){
            $scope.p.detalle.forEach(function(item, index){
                item.facturar = valor;
            });
            $scope.recalcular($scope.p);
        };

        $scope.ok = function () {
            $scope.recalcular($scope.p);
            $uibModalInstance.close($scope.p);
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    facturacionctrl.controller('ModalRptPreliminarCtrl', ['$scope', '$uibModalInstance', 'toaster', '$filter', 'empresas', 'proyectoSrvc', function($scope, $uibModalInstance, toaster, $filter, empresas, proyectoSrvc){
        $scope.empresas = empresas;
        $scope.proyectos = [];
        $scope.params = { fdelstr: '', falstr: '', empresa: '', proyecto: '', tc: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), objEmpresa: undefined, objProyecto: undefined, coniva: 0 };

        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });

        $scope.ok = function () {
            //$scope.params.idtipo = objectPropsToList($scope.params.objTipo, 'id', ',');
            $scope.params.empresa = $scope.params.objEmpresa != null && $scope.params.objEmpresa != undefined ? objectPropsToList($scope.params.objEmpresa, 'id', ',') : '';
            $scope.params.proyecto = $scope.params.objProyecto != null && $scope.params.objProyecto != undefined ? objectPropsToList($scope.params.objProyecto, 'id', ',') : '';
            $scope.params.tc = $scope.params.tc != null && $scope.params.tc != undefined ? $scope.params.tc : '';
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.coniva = $scope.params.coniva != null && $scope.params.coniva != undefined ? $scope.params.coniva : 0;

            $uibModalInstance.close($scope.params);
        };
        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);


}());
