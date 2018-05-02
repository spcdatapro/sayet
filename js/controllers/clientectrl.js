(function(){

    var clientectrl = angular.module('cpm.clientectrl', ['cpm.clientesrvc']);

    clientectrl.controller('clienteCtrl', ['$scope', 'clienteSrvc', 'DTOptionsBuilder', 'authSrvc', 'cuentacSrvc', 'noOrdenSrvc', '$confirm', 'empresaSrvc', 'tipoClienteSrvc', 'monedaSrvc', 'proyectoSrvc', '$filter', 'tipoServicioVentaSrvc', 'periodicidadSrvc', 'Upload', '$uibModal', 'jsReportSrvc', '$sce', 'toaster', 'tipoIpcSrvc', '$window', function($scope, clienteSrvc, DTOptionsBuilder, authSrvc, cuentacSrvc, noOrdenSrvc, $confirm, empresaSrvc, tipoClienteSrvc, monedaSrvc, proyectoSrvc, $filter, tipoServicioVentaSrvc, periodicidadSrvc, Upload, $uibModal, jsReportSrvc, $sce, toaster, tipoIpcSrvc, $window){

        //Variables de detalle
        $scope.clientes = [];
        $scope.detsfact = [];
        $scope.fiadores = [];
        $scope.contratos = [];
        $scope.lstdetfcontrato = [];
        $scope.lstadjcont = [];
        //Fin de variables de detalle
        $scope.cliente = {};
        $scope.clientePrint = {};
        $scope.usrdata = {};
        $scope.cuentasc = [];
        $scope.idempresa = 0;
        $scope.noorden = [];
        $scope.detfact = {};
        $scope.clientestr = '';
        $scope.fiador = {};
        $scope.contrato = {};
        $scope.contratoPrint = {};
        $scope.empresas = [];
        $scope.tiposcliente = [];
        $scope.cuentasc = [];
        $scope.monedas = [];
        $scope.proyectos = [];
        $scope.unidades = [];
        $scope.unidadesdisponibles = [];
        $scope.cuentascCont = [];
        $scope.contratoStr = '';
        $scope.detfcontrato = {};
        $scope.tsventa = [];
        $scope.fltrtsventa = [];
        $scope.periodicidad = [];
        $scope.unidadesStr = '';
        $scope.lectura = true;
        $scope.sldatafact = true;
        $scope.slfia = true;
        $scope.slcont = true;
        $scope.sldetcont = true;
        $scope.grpBtnCliente = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnDataFact = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnFia = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnCont = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnDetCont = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.today = moment().toDate();
        $scope.showForm = {contrato: false, detfact: false, fia: false, detcont: false, adjcont:false};
        $scope.adjcont = {};
        $scope.file = null;
        $scope.progressPercentage = 0;
        $scope.cargos = [];
        $scope.cargo = {};
        $scope.params = {idcontrato: 0, del:null, al:null, idtiposervicio: "0"};
        $scope.paramscli = {idcliente: 0};
        $scope.lsttipoipc = [];
        $scope.grabando = false;

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('paging', false);
        $scope.dtOptionsDetCont = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('ordering', false).withOption('paging', false);

        authSrvc.getSession().then(function(usrLogged){
            $scope.idempresa = parseInt(usrLogged.workingon);
            $scope.usrdata = usrLogged;
            //console.log($scope.usrdata);
            cuentacSrvc.getByTipo($scope.idempresa, 0).then(function(d){ $scope.cuentasc = d; });
            $scope.resetElCliente();
        });

        noOrdenSrvc.lstNoOrden().then(function(d){ $scope.noorden = d; });
        tipoServicioVentaSrvc.lstTSVenta().then(function(d){
            $scope.tsventa = d;
            $scope.fltrtsventa = d;
            $scope.fltrtsventa.push({id: "0", desctiposervventa: "Todos"});
        });

        tipoIpcSrvc.lstTipoIpc().then(function(d){ $scope.lsttipoipc = d; });

        $scope.confGrpBtnCliente = function(grp, i, u, d, a, e, c){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c;";
            eval(instruccion);
        };

        $scope.btnA = function(){
            $scope.lectura = false;
            $scope.resetElCliente();
            $scope.confGrpBtnCliente('grpBtnCliente', true, false, false, false, false, true);
        };

        $scope.btnC = function(){
            if($scope.cliente.id > 0){
                $scope.getCliente($scope.cliente.id);
            }else{
                $scope.resetElCliente();
            }
            $scope.confGrpBtnCliente('grpBtnCliente', false, false, false, true, false, false);
            $scope.lectura = true;
        };

        $scope.btnE = function(){
            $scope.lectura = false;
            $scope.confGrpBtnCliente('grpBtnCliente', false, true, true, false, false, true);
            goTop();
        };

        $scope.btnADF = function(){
            $scope.sldatafact = false;
            $scope.resetDetFact();
            $scope.confGrpBtnCliente('grpBtnDataFact', true, false, false, false, false, true);
        };

        $scope.btnCDF = function(){
            if($scope.detfact.id > 0){
                $scope.getDetFact($scope.detfact.id);
            }else{
                $scope.resetDetFact();
            }
            $scope.confGrpBtnCliente('grpBtnDataFact', false, false, false, true, false, false);
            $scope.sldatafact = true;
        };

        $scope.btnEDF = function(){
            $scope.sldatafact = false;
            $scope.confGrpBtnCliente('grpBtnDataFact', false, true, true, false, false, true);
            goTop();
        };

        $scope.btnAFia = function(){
            $scope.slfia = false;
            $scope.resetFiador();
            $scope.confGrpBtnCliente('grpBtnFia', true, false, false, false, false, true);
        };

        $scope.btnCFia = function(){
            if($scope.fiador.id > 0){
                $scope.getFiador($scope.fiador.id);
            }else{
                $scope.resetFiador();
            }
            $scope.confGrpBtnCliente('grpBtnFia', false, false, false, true, false, false);
            $scope.slfia = true;
        };

        $scope.btnEFia = function(){
            $scope.slfia = false;
            $scope.confGrpBtnCliente('grpBtnFia', false, true, true, false, false, true);
            goTop();
        };

        $scope.resetElCliente = function(){
            $scope.cliente = {
                id: 0, nombre: '', nombrecorto: '', direntrega: '', dirplanta: '', telpbx: '', teldirecto: '', telfax: '', telcel: '', correo: '', idordencedula: 0, regcedula: '', dpi: '',
                cargolegal: '', nomlegal: '', apellidolegal: '', nomadmon: '', mailadmon: '', nompago: '', mailcont: '', idcuentac: '', creadopor: $scope.usrdata.usuario
            };
            $scope.clientestr = '';
            $scope.detsfact = [];
            $scope.fiadores = [];
            $scope.contratos = [];
            $scope.lstdetfcontrato = [];
            $scope.lstadjcont = [];
            $scope.resetDetFact();
            $scope.resetFiador();
            $scope.resetContrato();
            $scope.grpBtnCliente = {i: false, u: false, d: false, a: true, e: false, c: false};
            goTop();
        };

        $scope.resetDetFact = function(){
            $scope.detfact = { idcliente: $scope.cliente.id != null && $scope.cliente.id != undefined ? $scope.cliente.id : 0, facturara: '', direccion: '', nit: '', fdel: '', fal: '', retiva: 0, retisr: 0 };
            $scope.grpBtnDataFact = {i: false, u: false, d: false, a: true, e: false, c: false};
        };

        $scope.resetContrato = function(){
            $scope.contrato = {
                idcliente: $scope.cliente.id != null && $scope.cliente.id != undefined ? $scope.cliente.id : 0, nocontrato: '', abogado: '', inactivo: 0,
                fechainicia: null, fechavence: null, nuevarenta: 0, nuevomantenimiento: 0, idmoneda: 0, idempresa: 0,
                deposito: 0, idproyecto: 0, idunidad: 0, retiva: 0, prorrogable: 1, retisr: 0, documento: 0, adelantado: 0, subarrendado: 0, idtipocliente: 0, idcuentac: '', observaciones: '',
                objMoneda: null, objEmpresa: null, objProyecto: null, objUnidad: [], objTipoCliente: null, reciboprov: '', objPeriodicidad: null, fechainactivo: undefined
            };
            $scope.contratoStr = '';
            $scope.unidadesStr = '';
            $scope.unidades = [];
            $scope.unidadesdisponibles = [];
            $scope.lstdetfcontrato = [];
            $scope.lstadjcont = [];
            $scope.grpBtnCont = {i: false, u: false, d: false, a: true, e: false, c: false};
            $scope.grpBtnDetCont = {i: false, u: false, d: false, a: true, e: false, c: false};
            //console.log('Variable contrato reseteado...');
            $scope.resetDetFContrato();
            goTop();
        };

        function procDataCliente(d){
            var tmpCont = [];
            var arrObjCont = [];
            var tmp = [];
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idordencedula = parseInt(d[i].idordencedula);
                d[i].fhcreacion = moment(d[i].fhcreacion).toDate();
                d[i].fhactualizacion = moment(d[i].fhactualizacion).isValid() ? moment(d[i].fhactualizacion).toDate() : null;
                tmpCont = d[i].contratos != null && d[i].contratos != undefined ? d[i].contratos.split(';') : [];
                for(var x = 0; x < tmpCont.length; x++){
                    tmp = tmpCont[x].split('_');
                    arrObjCont.push({idcontrato: tmp[0], nocontrato: tmp[1]});
                }
                d[i].conts = arrObjCont;
                arrObjCont = [];
            }
            return d;
        }

        function procDetFact(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idcliente = parseInt(d[i].idcliente);
                d[i].fdel = moment(d[i].fdel).isValid() ? moment(d[i].fdel).toDate() : null;
                d[i].fal = moment(d[i].fal).isValid() ? moment(d[i].fal).toDate() : null;
                d[i].retisr = parseInt(d[i].retisr);
                d[i].retiva = parseInt(d[i].retiva);
            }
            return d;
        }

        $scope.getLstClientes = function(){
            clienteSrvc.lstCliente().then(function(d){
                $scope.clientes = procDataCliente(d);
            });
        };

        $scope.resetRptParams = function(){
            $scope.params = {idcontrato: 0, del:null, al:null, idtiposervicio: "0"};
            $scope.paramscli = {idcliente: 0};
        };

        $scope.getCliente = function(idcliente){
            $scope.detsfact = [];
            $scope.fiadores = [];
            $scope.contratos = [];
            $scope.lstdetfcontrato = [];
            $scope.lstadjcont = [];
            clienteSrvc.getCliente(idcliente).then(function(d){
                $scope.cliente = procDataCliente(d)[0];
                $scope.getLstDetFact(idcliente);
                $scope.resetDetFact();
                $scope.resetContrato();
                $scope.clientestr = $scope.cliente.nombre + ' (' + $scope.cliente.nombrecorto + ')';
                monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });
                empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
                proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });
                tipoClienteSrvc.lstTiposCliente().then(function(d){ $scope.tiposcliente = d; });
                periodicidadSrvc.lstPeriodicidad().then(function(d){ $scope.periodicidad = d; });
                $scope.getLstContratos(idcliente);
                $scope.resetDetFContrato();
                $scope.confGrpBtnCliente('grpBtnCliente', false, false, true, true, true, false);
                $scope.lectura = true;
                moveToTab('divContDatFact', 'divContDatGen');
                moveToTab('divLstClientes', 'divFrmCliente');
                goTop();
            });
        };

        //var test = true;

        $scope.printCliente = function(idcliente, fromTab){
            $scope.resetRptParams();
            $scope.paramscli.idcliente = idcliente;
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalPrintCliente.html',
                controller: 'ModalPrintClienteCtrl',
                windowClass: 'app-modal-window',
                resolve:{ params: function(){return $scope.paramscli;} }
            });

            modalInstance.result.then(function(){
                //console.log('Modal cerrada')
            }, function(){ return 0; });

            //jsReportSrvc.getPDFReport(test ? 'HJfp-erkg' : 'SyZ8gmHkx', $scope.paramscli).then(function(pdf){ $scope.contentcli = pdf; });
        };

        $scope.printContrato = function(idcontrato, fromTab){
            $scope.params.idcontrato = idcontrato;
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalPrintContrato.html',
                controller: 'ModalPrintContratoCtrl',
                windowClass: 'app-modal-window',
                resolve:{
                    params: function(){return $scope.params;}
                }
            });

            modalInstance.result.then(function(){
                //console.log('Modal cerrada')
            }, function(){ return 0; });
        };

        $scope.printContratoInactivo = function(idcontrato){
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'rkMagHAbG' : 'rJFcpHA-M', {idcontrato: +idcontrato}).then(function(pdf){ $window.open(pdf); });
        };

        $scope.sendToPrint = function(idElemento, titulo){ PrintElem('#' + idElemento, titulo); };

        $scope.getCuentasC = function(idempresa){
            cuentacSrvc.getByTipo(idempresa, 0).then(function(d){ $scope.cuentascCont = d; });
            proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then(function(d){
                $scope.proyectos = d;
                $scope.contrato.objProyecto = $filter('getById')($scope.proyectos, $scope.contrato.idproyecto);
            });
        };

        $scope.$watch('contrato.objEmpresa', function(newVal, oldVal){
            if(newVal != null && newVal != undefined){
                $scope.getCuentasC(+newVal.id);
            }
        });

        $scope.getLstUnidadesXProy = function(idproyecto){
            proyectoSrvc.lstUnidadesProyecto(parseInt(idproyecto)).then(function(d){ $scope.unidades = d; });
        };

        $scope.getLstUnidadesDisponibles = function(idproyecto, idcontrato){
            if(isNaN(+idcontrato)){ idcontrato = 0; }
            if(!isNaN(+idproyecto)){ proyectoSrvc.lstUnidadesDisponibles(+idproyecto, +idcontrato).then(function(d){ $scope.unidadesdisponibles = d; }); }
        };

        $scope.doGetLstUnidadesDisponibles = function($item, $model){
            $scope.getLstUnidadesDisponibles($item.id, $scope.contrato.id);
        };

        $scope.addCliente = function(obj){
            obj.idcuentac = '';
            obj.idordencedula = obj.objOrdenCedula != null && obj.objOrdenCedula != undefined ? obj.objOrdenCedula.id : 0;
            clienteSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstClientes();
                $scope.getCliente(parseInt(d.lastid));
            });

        };

        $scope.updCliente = function(obj){
            //obj.id = idcliente;
            obj.idcuentac = '';
            obj.idordencedula = obj.objOrdenCedula != null && obj.objOrdenCedula != undefined ? obj.objOrdenCedula.id : 0;
            clienteSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstClientes();
                $scope.getCliente(parseInt(obj.id));
            });
        };

        $scope.delCliente = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar al cliente ' + obj.nombre + '?', title: 'Eliminar cliente', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'd').then(function(){
                    $scope.getLstClientes();
                    $scope.resetElCliente();
                    $scope.btnC();
                });
            });
        };

        $scope.getLstDetFact = function(idcliente){ clienteSrvc.lstDetFact(idcliente).then(function(d){ $scope.detsfact = procDetFact(d); }); };

        $scope.getDetFact = function(iddetfact){
            clienteSrvc.getDetFact(iddetfact).then(function(d){
                $scope.detfact = procDetFact(d)[0];
                $scope.confGrpBtnCliente('grpBtnDataFact', false, false, true, true, true, false);
                $scope.sldatafact = true;
                $scope.showForm.detfact = true;
            })
        };

        $scope.addDetFact = function(obj){
            obj.idcliente = $scope.cliente.id;
            obj.fdelstr = obj.fdel != null && obj.fdel != undefined ? moment(obj.fdel).format('YYYY-MM-DD') : '';
            obj.falstr = obj.fal != null && obj.fal != undefined ? moment(obj.fal).format('YYYY-MM-DD') : '';
            obj.emailfactura = obj.emailfactura != null && obj.emailfactura != undefined ? obj.emailfactura : '';
            obj.retisr = obj.retisr != null && obj.retisr != undefined ? obj.retisr : 0;
            obj.retiva = obj.retiva != null && obj.retiva != undefined ? obj.retiva : 0;
            clienteSrvc.editRow(obj, 'cdf').then(function(d){
                $scope.getLstDetFact(obj.idcliente);
                $scope.btnCDF();
            });
        };

        $scope.updDetFact = function(obj){
            obj.idcliente = $scope.cliente.id;
            obj.fdelstr = obj.fdel != null && obj.fdel != undefined ? moment(obj.fdel).format('YYYY-MM-DD') : '';
            obj.falstr = obj.fal != null && obj.fal != undefined ? moment(obj.fal).format('YYYY-MM-DD') : '';
            obj.emailfactura = obj.emailfactura != null && obj.emailfactura != undefined ? obj.emailfactura : '';
            obj.retisr = obj.retisr != null && obj.retisr != undefined ? obj.retisr : 0;
            obj.retiva = obj.retiva != null && obj.retiva != undefined ? obj.retiva : 0;
            clienteSrvc.editRow(obj, 'udf').then(function(){
                $scope.getLstDetFact(obj.idcliente);
                $scope.btnCDF();
            });
        };

        $scope.delDetFact = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este detalle de facturación?', title: 'Eliminar detalle de facturación', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'ddf').then(function(){ $scope.getLstDetFact(obj.idcliente); $scope.resetDetFact(); $scope.btnCDF(); });
            });
        };

        $scope.detServicios = function(obj){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalDetServicios.html',
                controller: 'ModalDetServiciosCtrl',
                resolve:{ detfact: function(){ return obj; } }
            });

            modalInstance.result.then(function(){
                $scope.getLstDetFact(+$scope.cliente.id);
            }, function(){
                $scope.getLstDetFact(+$scope.cliente.id);
            });
        };

        $scope.resetFiador = function(){
            $scope.fiador = { idcliente: $scope.cliente.id != null && $scope.cliente.id != undefined ? $scope.cliente.id : 0, nombre: '', direccion: '', telefono: '', identificacion: '', empresa:'' };
            $scope.grpBtnFia = {i: false, u: false, d: false, a: true, e: false, c: false};
        };

        $scope.getFiadores = function(idcontrato){ clienteSrvc.lstFiadores(idcontrato).then(function(d){ $scope.fiadores = d; });};
        $scope.getFiador = function(iddetfia){
            clienteSrvc.getFiador(iddetfia).then(function(d){
                $scope.fiador = d[0];
                $scope.confGrpBtnCliente('grpBtnFia', false, false, true, true, true, false);
                $scope.slfia = true;
                $scope.showForm.fia = true;
            });
        };

        $scope.addFiador = function(obj){
            obj.idcontrato = $scope.contrato.id;
            obj.empresa = obj.empresa != null && obj.empresa != undefined ? obj.empresa : '';
            clienteSrvc.editRow(obj, 'cfia').then(function(d){
                $scope.getFiadores(obj.idcontrato);
                $scope.getFiador(parseInt(d.lastid));
            });
        };

        $scope.updFiador = function(obj){
            obj.idcontrato = $scope.contrato.id;
            obj.empresa = obj.empresa != null && obj.empresa != undefined ? obj.empresa : '';
            clienteSrvc.editRow(obj, 'ufia').then(function(){
                $scope.getFiadores(obj.idcontrato);
                $scope.getFiador(parseInt(obj.id));
            });
        };

        $scope.delFiador = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar a ' + obj.nombre + ' como fiador?', title: 'Eliminar fiador', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'dfia').then(function(){ $scope.getFiadores(obj.id); $scope.resetFiador(); $scope.btnCFia(); });
            });
        };

        $scope.btnACont = function(){
            $scope.slcont = false;
            $scope.resetContrato();
            $scope.confGrpBtnCliente('grpBtnCont', true, false, false, false, false, true);
        };

        $scope.btnCCont = function(){
            if($scope.contrato.id > 0){
                $scope.getContrato($scope.contrato.id);
            }else{
                $scope.resetContrato();
            }
            $scope.confGrpBtnCliente('grpBtnCont', false, false, false, true, false, false);
            $scope.slcont = true;
        };

        $scope.btnECont = function(){
            $scope.slcont = false;
            $scope.confGrpBtnCliente('grpBtnCont', false, true, true, false, false, true);
            goTop();
        };

        function procDataContratos(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idcliente = parseInt(d[i].idcliente);
                d[i].inactivo = parseInt(d[i].inactivo);
                d[i].idmoneda = parseInt(d[i].idmoneda);
                d[i].idempresa= parseInt(d[i].idempresa);
                d[i].idproyecto = parseInt(d[i].idproyecto);
                d[i].retiva = parseInt(d[i].retiva);
                d[i].prorrogable = parseInt(d[i].prorrogable);
                d[i].retisr = parseInt(d[i].retisr);
                d[i].documento = parseInt(d[i].documento);
                d[i].adelantado = parseInt(d[i].adelantado);
                d[i].subarrendado = parseInt(d[i].subarrendado);
                d[i].idtipocliente = parseInt(d[i].idtipocliente);
                d[i].idperiodicidad = parseInt(d[i].idperiodicidad);
                //d[i].idtipoipc = parseInt(d[i].idtipoipc);
                d[i].cobro = parseInt(d[i].cobro);
                d[i].prescision = parseInt(d[i].prescision);
                d[i].nuevarenta = parseFloat(parseFloat(d[i].nuevarenta).toFixed(2));
                d[i].nuevomantenimiento = parseFloat(parseFloat(d[i].nuevomantenimiento).toFixed(2));
                d[i].idmonedadep = parseInt(d[i].idmonedadep);
                d[i].deposito = parseFloat(parseFloat(d[i].deposito).toFixed(2));
                d[i].fechainicia = moment(d[i].fechainicia).toDate();
                d[i].fechavence = moment(d[i].fechavence).toDate();
                d[i].plazofdel = moment(d[i].plazofdel).isValid() ? moment(d[i].plazofdel).toDate() : null;
                d[i].plazofal = moment(d[i].plazofal).isValid() ? moment(d[i].plazofal).toDate() : null;
                d[i].fechainactivo = moment(d[i].fechainactivo).isValid() ? moment(d[i].fechainactivo).toDate() : undefined;
            }
            return d;
        }

        $scope.goToContrato = function(idcliente, idcontrato){
            clienteSrvc.getCliente(parseInt(idcliente)).then(function(d){
                $scope.cliente = procDataCliente(d)[0];
                $scope.getLstDetFact(idcliente);
                $scope.resetDetFact();
                $scope.resetFiador();
                $scope.resetContrato();
                $scope.clientestr = $scope.cliente.nombre + ' (' + $scope.cliente.nombrecorto + ')';
                monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });
                empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
                proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });
                tipoClienteSrvc.lstTiposCliente().then(function(d){ $scope.tiposcliente = d; });
                periodicidadSrvc.lstPeriodicidad().then(function(d){ $scope.periodicidad = d; });
                clienteSrvc.lstContratos(parseInt(idcliente)).then(function(d){
                    $scope.contratos = procDataContratos(d);
                    $scope.getContrato(parseInt(idcontrato));
                    moveToTab('divLstClientes', 'divContrato');
                });
                $scope.resetDetFContrato();
                $scope.confGrpBtnCliente('grpBtnCliente', false, false, true, true, true, false);
                $scope.lectura = true;
            });
        };

        $scope.getLstContratos = function(idcliente){
            clienteSrvc.lstContratos(idcliente).then(function(d){
                $scope.contratos = procDataContratos(d);
            });
        };

        $scope.getContrato = function(idcontrato){
            $scope.lstdetfcontrato = [];
            $scope.lstadjcont = [];
            $scope.fiadores = [];
            $scope.resetFiador();
            clienteSrvc.getContrato(idcontrato).then(function(d){
                $scope.contrato = procDataContratos(d)[0];
                $scope.$broadcast('angucomplete-alt:changeInput', 'txtAbogado', {abogado: $scope.contrato.abogado});
                $scope.contrato.objMoneda = $filter('getById')($scope.monedas, $scope.contrato.idmoneda);
                $scope.contrato.objEmpresa = $filter('getById')($scope.empresas, $scope.contrato.idempresa);
                $scope.contrato.objProyecto = $filter('getById')($scope.proyectos, $scope.contrato.idproyecto);
                $scope.contrato.objMonedaDep = $filter('getById')($scope.monedas, $scope.contrato.idmonedadep);
                $scope.contrato.objPeriodicidad = $filter('getById')($scope.periodicidad, $scope.contrato.idperiodicidad);
                $scope.contrato.objUnidad = [];
                proyectoSrvc.lstUnidadesProyecto($scope.contrato.idproyecto).then(function(d){
                    $scope.unidades = d;
                    var lstUnidadesTmp = $scope.contrato.idunidad.split(','), lstUnidades = [];
                    //console.log(lstUnidadesTmp);
                    for(var j = 0; j < lstUnidadesTmp.length; j++){
                        if(lstUnidades.indexOf(lstUnidadesTmp[j]) == -1){
                            lstUnidades.push(lstUnidadesTmp[j]);
                        }
                    }
                    //console.log(lstUnidades);
                    for(var i = 0; i < lstUnidades.length; i++){
                        $scope.contrato.objUnidad.push( $filter('getById')($scope.unidades, parseInt(lstUnidades[i])) );
                    }
                    $scope.unidadesStr = objectPropsToList($scope.contrato.objUnidad, 'nombre', ', ');
                });

                $scope.getLstUnidadesDisponibles($scope.contrato.idproyecto, $scope.contrato.id);

                $scope.contrato.objTipoCliente = $filter('getById')($scope.tiposcliente, $scope.contrato.idtipocliente);
                cuentacSrvc.getByTipo($scope.contrato.idempresa, 0).then(function(d){ $scope.cuentascCont = d; });
                $scope.contratoStr = 'No. ' + $scope.contrato.nocontrato;
                $scope.resetDetFContrato();
                $scope.getLstDetFContrato(idcontrato);
                //tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tsventa = d; });
                //periodicidadSrvc.lstPeriodicidad().then(function(d){ $scope.periodicidad = d; });
                $scope.getFiadores(idcontrato);
                $scope.getLstAdjuntosCont(idcontrato);
                $scope.adjcont = {};

                $scope.confGrpBtnCliente('grpBtnCont', false, false, true, true, true, false);
                $scope.slcont = true;
                $scope.showForm.contrato = true;
                goTop();
            });
        };

        $scope.showModInactivo = function(){
            if(+$scope.contrato.inactivo === 1){
                var modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: 'modalInactivo.html',
                    controller: 'ModalInactivoCtrl',
                    resolve:{ contrato: function(){ return $scope.contrato; } }
                });

                modalInstance.result.then(function(fecSelected){
                    //console.log(fecSelected);
                    $scope.contrato.fechainactivo = fecSelected;
                }, function(){
                    if(!moment($scope.contrato.fechainactivo).isValid()){
                        $scope.contrato.inactivo = 0;
                        $scope.contrato.fechainactivo = undefined;
                    }
                });
            }else{ $scope.contrato.fechainactivo = undefined; }
        };

        function procObjContract(obj){
            obj.inactivo = obj.inactivo != null && obj.inactivo != undefined ? obj.inactivo : 0;
            obj.idmoneda = obj.objMoneda != null && obj.objMoneda != undefined ? obj.objMoneda.id : 0;
            obj.idmonedadep = obj.objMonedaDep != null && obj.objMonedaDep != undefined ? obj.objMonedaDep.id : 0;
            obj.idempresa = obj.objEmpresa != null && obj.objEmpresa != undefined ? obj.objEmpresa.id : 0;
            obj.idproyecto = obj.objProyecto != null && obj.objProyecto != undefined ? obj.objProyecto.id : 0;
            obj.idunidad = obj.objUnidad != null && obj.objUnidad != undefined ? objectPropsToList(obj.objUnidad, 'id', ',') : '';
            obj.idtipocliente = obj.objTipoCliente != null && obj.objTipoCliente != undefined ? obj.objTipoCliente.id : 0;
            obj.fechainiciastr = moment(obj.fechainicia).format('YYYY-MM-DD');
            obj.fechavencestr = moment(obj.fechavence).format('YYYY-MM-DD');
            obj.reciboprov = obj.reciboprov != null && obj.reciboprov != undefined ? obj.reciboprov : '';
            obj.idperiodicidad = obj.objPeriodicidad.id;
            obj.lastuser = parseInt($scope.usrdata.uid);
            obj.cobro = obj.cobro != null && obj.cobro != undefined ? obj.cobro : 0;
            obj.prescision = obj.prescision != null && obj.prescision != undefined ? obj.prescision : 0;
            obj.plazofdelstr = moment(obj.plazofdel).isValid() ? moment(obj.plazofdel).format('YYYY-MM-DD') : '';
            obj.plazofalstr = moment(obj.plazofal).isValid() ? moment(obj.plazofal).format('YYYY-MM-DD') : '';
            obj.fechainactivostr = moment(obj.fechainactivo).isValid() ? moment(obj.fechainactivo).format('YYYY-MM-DD') : '';

            // if(+obj.idtipocliente == 3){
                obj.retiva = 0;
                obj.retisr = 0;
            //}

            return obj;
        }

        $scope.abogadoSelected = function(item){
            if(item != null && item != undefined){
                switch(typeof item.originalObject){
                    case 'string':
                        $scope.contrato.abogado = item.originalObject;
                        break;
                    case 'object':
                        $scope.contrato.abogado = item.originalObject.abogado;
                        break;
                }
            }
        };

        $scope.addContrato = function(obj){
            obj = procObjContract(obj);
            //console.log(obj); return;
            clienteSrvc.editRow(obj, 'cc').then(function(d){
                $scope.getLstContratos(obj.idcliente);
				$scope.getLstClientes();
                $scope.getContrato(parseInt(d.lastid));
            });
        };

        $scope.updContrato = function(obj){
            obj = procObjContract(obj);
            //console.log(obj); return;
            clienteSrvc.editRow(obj, 'uc').then(function(d){
                $scope.getLstContratos(obj.idcliente);
				$scope.getLstClientes();
                $scope.getContrato(parseInt(obj.id));
            });
        };

        $scope.delContrato = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el contrato No. ' + obj.nocontrato + '?', title: 'Eliminar contrato', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'dc').then(function(){ $scope.getLstContratos(obj.idcliente); $scope.getLstClientes(); $scope.resetContrato(); $scope.btnCCont(); });
            });
        };

        $scope.checkTipoCliente = function(idtipocliente){
            if(+idtipocliente == 3){
                $scope.contrato.retiva = 0;
                $scope.contrato.retisr = 0;
            }
        };

        $scope.btnADetCont = function(){
            $scope.sldetcont = false;
            $scope.resetDetFContrato();
            $scope.confGrpBtnCliente('grpBtnDetCont', true, false, false, false, false, true);
        };

        $scope.btnCDetCont = function(){
            if($scope.detfcontrato.id > 0){
                $scope.getDetFContrato($scope.detfcontrato.id);
            }else{
                $scope.resetDetFContrato();
            }
            $scope.confGrpBtnCliente('grpBtnDetCont', false, false, false, true, false, false);
            $scope.sldetcont = true;
        };

        $scope.btnEDetCont = function(){
            $scope.sldetcont = false;
            $scope.confGrpBtnCliente('grpBtnDetCont', false, true, true, false, false, true);
            goTop();
        };

        $scope.resetDetFContrato = function(){
            $scope.detfcontrato = {
                idcontrato: $scope.contrato != null && $scope.contrato != undefined ? $scope.contrato.id : 0,
                fdel: null, fal: null,
                monto: 0, idtipoventa: 0, idmoneda: 0, cobro: 0, idperiodicidad: 0, noperido: 1
            };
            $scope.grpBtnDetCont = {i: false, u: false, d: false, a: true, e: false, c: false};
            //console.log('Variable detalle de contrato reseteada...');
            goTop();
        };

        function procDetFContrato(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idcontrato = parseInt(d[i].idcontrato);
                d[i].idtipoventa = parseInt(d[i].idtipoventa);
                d[i].idmoneda = parseInt(d[i].idmoneda);
                d[i].cobro = parseInt(d[i].cobro);
                d[i].idperiodicidad = parseInt(d[i].idperiodicidad);
                d[i].noperiodo = parseInt(d[i].noperiodo);
                d[i].fuerarango = parseInt(d[i].fuerarango);
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                d[i].fdel = moment(d[i].fdel).toDate();
                d[i].fal = moment(d[i].fal).toDate();
                d[i].fechavence = moment(d[i].fechavence).toDate();
            }
            return d;
        }

        function procObjDetFContrato(obj){
            obj.fdelstr = moment(obj.fdel).format('YYYY-MM-DD');
            obj.falstr = moment(obj.fal).format('YYYY-MM-DD');
            obj.idtipoventa = obj.objTipoVenta.id;
            obj.idmoneda = obj.objMoneda.id;
            obj.idperiodicidad = $scope.contrato.idperiodicidad;
            return obj;
        }

        $scope.getLstDetFContrato = function(idcontrato){
            clienteSrvc.lstDetFContrato(idcontrato).then(function(d){
                $scope.lstdetfcontrato = procDetFContrato(d);
            })
        };

        $scope.detcontfacturado = false;
        $scope.facturasperiodo = 0;
        $scope.dataanula = {usuario: ''};

        $scope.getDetFContrato = function(iddet){
            $scope.detcontfacturado = false;
            $scope.facturasperiodo = 0;
            $scope.dataanula = {usuario: ''};
            clienteSrvc.getDetFContrato(iddet).then(function(d){
                $scope.detfcontrato = procDetFContrato(d)[0];
                $scope.detfcontrato.objTipoVenta = $filter('getById')($scope.tsventa, parseInt($scope.detfcontrato.idtipoventa));
                $scope.detfcontrato.objMoneda = $filter('getById')($scope.monedas, parseInt($scope.detfcontrato.idmoneda));
                $scope.confGrpBtnCliente('grpBtnDetCont', false, false, true, true, true, false);
                $scope.sldetcont = true;
                $scope.showForm.detcont = true;

                clienteSrvc.chkDetFContratoFacturado(iddet).then(function(d){
                    if(+d.facturado != 0){
                        $scope.detcontfacturado = true;
                        $scope.facturasperiodo = +d.facturado;
                        clienteSrvc.chkDetFContratoAnulado(iddet).then(function(d){
                            $scope.dataanula = d[0];
                        });
                    }
                });

                goTop();
            });
        };

        $scope.chkFecha = function(fechaal){
            var ffincont = $scope.contrato.fechavence;
            if(moment(fechaal).isValid()){
                if(moment(fechaal).isAfter(ffincont)){
                    toaster.pop('warning', 'Error en fechas',
                        'La fecha final de este período (' + moment(fechaal).format('DD/MM/YYYY') +
                        ') esta fuera de la fecha de vencimiento (' + moment(ffincont).format('DD/MM/YYYY') + ') del contrato. Favor revisar.',
                        'timeout:10000'
                    );
                    //$scope.detfcontrato.fal = null;
                }
            }
        };

        $scope.addDetFContrato = function(obj){
            $scope.grabando = true;
            obj = procObjDetFContrato(obj);
            //console.log(obj); return;
            clienteSrvc.editRow(obj, 'cdc').then(function(d){
                $scope.getLstDetFContrato(obj.idcontrato);
                $scope.getDetFContrato(parseInt(d.lastid));
                clienteSrvc.editRow({id:parseInt(d.lastid)}, 'gencobros').then(function(){
                    toaster.pop('info', 'Cobros', 'Se generaron los cobros de este período...', 'timeout:1500');
                    $scope.grabando = false;
                });
            });
        };

        $scope.updDetFContrato = function(obj){
            obj = procObjDetFContrato(obj);
            $confirm({text: 'Este proceso eliminará los cargos existentes de este período y creará nuevos en base a las modificaciones. ¿Seguro(a) de continuar?',
                title: 'Actualización de período', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow(obj, 'udc').then(function(d){
                    $scope.getLstDetFContrato(obj.idcontrato);
                    $scope.getDetFContrato(parseInt(obj.id));
                    clienteSrvc.editRow({id:parseInt(obj.id)}, 'gencobros').then(function(){
                        toaster.pop('info', 'Cobros', 'Se generaron los cobros de este período...', 'timeout:1500');
                    });
                });
            });
        };

        $scope.delDetFContrato = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este detalle de facturación?', title: 'Eliminar detalle', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'ddc').then(function(){ $scope.getLstDetFContrato(obj.idcontrato); $scope.resetDetFContrato(); $scope.btnCDetCont(); });
            });
        };

        $scope.anulaPeriodo = function(iddet){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalAnulaCargos.html',
                controller: 'ModalAnulaCargosCtrl',
                resolve:{
                    iddet: function(){ return iddet; },
                    usr: function(){ return $scope.usrdata; }
                }
            });

            modalInstance.result.then(function(){
                //console.log('Modal cerrada')
            }, function(){ return 0; });
        };

        function procDataCargos(d){
            //console.log(d); return;
            for(var i = 0; i < d.length; i++){
                //console.log(d[i]);
                d[i].id = parseInt(d[i].id);
                d[i].iddetcont = parseInt(d[i].iddetcont);
                d[i].fechacobro = moment(d[i].fechacobro).toDate();
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                d[i].descuento = parseFloat(parseFloat(d[i].descuento).toFixed(2));
                d[i].facturado = parseInt(d[i].facturado);
            }
            return d;
        }

        $scope.generaCobros = function(iddetfcont){
            $scope.cargos = [];
            clienteSrvc.lstCargos(iddetfcont).then(function(d){
                //console.log(d); return;
                if(d.length > 0){
                    $scope.cargos = procDataCargos(d);

                    var modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: 'modalCargos.html',
                        controller: 'ModalCargosCtrl',
                        resolve:{
                            cargos: function(){return $scope.cargos;}
                        }
                    });

                    modalInstance.result.then(function(){
                        //console.log('Modal cerrada')
                    }, function(){ return 0; });
                }else{
                    clienteSrvc.editRow({id:iddetfcont}, 'gencobros').then(function(){ $scope.generaCobros(iddetfcont); });
                    /*
                    $confirm({text: '¿Seguro(a) de generar los cobros para este período?', title: 'Generación de cobros', ok: 'Sí', cancel: 'No'}).then(function() {
                        clienteSrvc.editRow({id:iddetfcont}, 'gencobros').then(function(){ $scope.generaCobros(iddetfcont); });
                    });
                    */
                }
            });
        };

        // upload later on form submit or something similar
        $scope.submit = function() {
            if ($scope.file) {
                $scope.upload($scope.file);
            }
        };

        // upload on file select or drop
        $scope.upload = function (file) {
            if(file){
                file.name = $filter('textCleaner')(file.name);
            }
            Upload.upload({
                url: 'php/upload.php',
                method: 'POST',
                file: file,
                sendFieldsAs: 'form',
                fields: {
                    directorio: '../contrato_adjunto/',
                    prefijo: 'CNT_' + $scope.contrato.id + '_'
                }
            }).then(function (resp) {
                $scope.file = null;
                $scope.progressPercentage = 0;
            }, function (resp) {

            }, function (evt) {
                $scope.progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
            });
        };

        $scope.getLstAdjuntosCont = function(idcontrato){
            clienteSrvc.lstAdjCont(parseInt(idcontrato)).then(function(d){
                $scope.lstadjcont = d;
            });
        };

        $scope.addContratoAdjunto = function(obj){
            $scope.submit();
            obj.idcontrato = $scope.contrato.id;
            obj.ubicacion = "contrato_adjunto/"+'CNT_'+$scope.contrato.id+'_'+$filter('textCleaner')($scope.file.name);
            clienteSrvc.editRow(obj, 'cac').then(function(){
                $scope.getLstAdjuntosCont(obj.idcontrato);
                $scope.adjcont = {};
            });

        };

        $scope.delAdjCont = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este adjunto?', title: 'Eliminar adjunto', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow({id:obj.id}, 'dac').then(function(){ $scope.getLstAdjuntosCont(obj.idcontrato); $scope.adjcont = {}; });
            });
        };

        $scope.getLstClientes();

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalCargosCtrl', ['$scope', '$uibModalInstance', 'toaster', 'cargos', 'clienteSrvc', function($scope, $uibModalInstance, toaster, cargos, clienteSrvc){
        $scope.cargos = cargos;
        $scope.sldesc = [];
        $scope.restar = $scope.cargos[0].id;

        $scope.$watch(function(){return cargos}, function(newVal, oldVal){
            $scope.sldesc = [];
            $scope.restar = $scope.cargos[0].id;
            //console.log($scope.restar);
            for(var i = 0; i < $scope.cargos.length; i++){ $scope.sldesc.push(true); }
        });

        $scope.habilitar = function(iddetcont){
            var rowIndex = iddetcont - $scope.restar;
            $scope.sldesc[rowIndex] = false;
        };

        function procDataCargos(d){
            //console.log(d); return;
            for(var i = 0; i < d.length; i++){
                //console.log(d[i]);
                d[i].id = parseInt(d[i].id);
                d[i].iddetcont = parseInt(d[i].iddetcont);
                d[i].fechacobro = moment(d[i].fechacobro).toDate();
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                d[i].descuento = parseFloat(parseFloat(d[i].descuento).toFixed(2));
                d[i].facturado = parseInt(d[i].facturado);
            }
            return d;
        }

        $scope.udpateDescuento = function(obj){
            var rowIndex = obj.id - $scope.restar;
            clienteSrvc.editRow(obj, 'udesccargo').then(function(){
                clienteSrvc.lstCargos(obj.iddetcont).then(function(d){
                    $scope.cargos = procDataCargos(d);
                    $scope.sldesc[rowIndex] = true;
                });
            });
        };


        //$scope.ok = function () { $uibModalInstance.close($scope.fcierre); };

        $scope.cancel = function () {
            $scope.sldesc = [];
            $uibModalInstance.dismiss('cancel');
        };

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalInactivoCtrl', ['$scope', '$uibModalInstance', 'toaster', 'contrato', function($scope, $uibModalInstance, toaster, contrato){
        $scope.contrato = contrato;
        $scope.fechainactivo = moment().toDate();

        $scope.ok = function () {
            if(moment($scope.fechainactivo).isValid()){
                $uibModalInstance.close($scope.fechainactivo);
            }else{
                toaster.pop('error', 'Parece que la fecha seleccionada no es valida. Intenta de nuevo, por favor.', 'timeout:5000');
            }
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalAnulaCargosCtrl', ['$scope', '$uibModalInstance', 'toaster', 'clienteSrvc', 'razonAnulacionSrvc', '$confirm', '$filter', 'iddet', 'usr', function($scope, $uibModalInstance, toaster, clienteSrvc, razonAnulacionSrvc, $confirm, $filter, iddet, usr){
        $scope.periodo = {};
        $scope.razones = [];
        $scope.obj = {idusuario: usr.uid, idrazonanula: undefined, iddetcontrato: iddet };

        clienteSrvc.getDetFContrato(iddet).then(function(d){ $scope.periodo = d[0]; });
        razonAnulacionSrvc.lstRazones().then(function(d){ $scope.razones = d; });

        $scope.ok = function () {
            $confirm({text: '¿Seguro(a) de anular los cargos restantes de este período No. ' + $filter('number')($scope.periodo.noperiodo, 0) + '?', title: 'Anular período', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow($scope.obj, 'apf').then(function(){ });
                toaster.pop('info', 'Anulación de cargos', 'Los cargos restantes del período No. ' + $filter('number')($scope.periodo.noperiodo, 0) + ' fueron anulados.', 'timeout:5000');
                $uibModalInstance.close();
            }, function(){
                $scope.cancel();
            });
        };

        $scope.cancel = function () { $uibModalInstance.dismiss('cancel'); };

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalDetServiciosCtrl', ['$scope', '$uibModalInstance', 'toaster', 'clienteSrvc', '$confirm', '$filter', 'detfact', 'tipoServicioVentaSrvc', function($scope, $uibModalInstance, toaster, clienteSrvc, $confirm, $filter, detfact, tipoServicioVentaSrvc){
        $scope.detfact = detfact;
        $scope.servicios = [];
        $scope.servicio = { iddetclientefact: $scope.detfact.id, idservicioventa: undefined };
        $scope.tiposservicio = [];

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tiposservicio = d; });

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () {
            $scope.ok();
            //$uibModalInstance.dismiss('cancel');
        };

        $scope.loadServFact = function(){
            clienteSrvc.lstDetServFact(+$scope.detfact.id).then(function(d){ $scope.servicios = d; });
        };

        $scope.resetServicio = function(){ $scope.servicio = { iddetclientefact: $scope.detfact.id, idservicioventa: undefined }; };

        $scope.addServicio = function(obj){
            clienteSrvc.editRow(obj, 'cddf').then(function(){
                $scope.loadServFact();
                $scope.resetServicio();
            });
        };

        $scope.delServicio = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el servicio "' + obj.servicio + '" a facturar de ' + $scope.detfact.facturara + '?', title: 'Eliminar servicio a facturar', ok: 'Sí', cancel: 'No'}).then(function() {
                clienteSrvc.editRow(obj, 'dddf').then(function(){ $scope.loadServFact(); $scope.resetServicio(); });
            });
        };

        $scope.loadServFact();

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalPrintContratoCtrl', ['$scope', '$uibModalInstance', 'toaster', 'clienteSrvc', '$confirm', '$filter', 'params', 'tipoServicioVentaSrvc', 'jsReportSrvc', function($scope, $uibModalInstance, toaster, clienteSrvc, $confirm, $filter, params, tipoServicioVentaSrvc, jsReportSrvc){
        $scope.tiposservicio = [];
        $scope.content = '';
        $scope.params = params;

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tiposservicio = d; });

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $scope.ok(); }; //$uibModalInstance.dismiss('cancel');

        var test = false;
        $scope.imprimir = function(){
            $scope.params.delstr = moment($scope.params.del).isValid() ? moment($scope.params.del).format('YYYY-MM-DD') : '0';
            $scope.params.alstr = moment($scope.params.al).isValid() ? moment($scope.params.al).format('YYYY-MM-DD') : '0';
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'SywTrJEJx' : 'HkPQ-mSkl', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.imprimir();

    }]);
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    clientectrl.controller('ModalPrintClienteCtrl', ['$scope', '$uibModalInstance', 'params', 'jsReportSrvc', function($scope, $uibModalInstance, params, jsReportSrvc){
        $scope.content = '';
        $scope.params = params;

        $scope.ok = function () { $uibModalInstance.close(); };
        $scope.cancel = function () { $scope.ok(); }; //$uibModalInstance.dismiss('cancel');

        var test = false;
        $scope.imprimir = function(){
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'HJfp-erkg' : 'SyZ8gmHkx', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.imprimir();

    }]);


}());
