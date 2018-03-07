(function(){

    var reciboclictrl = angular.module('cpm.reciboclictrl', []);

    reciboclictrl.controller('reciboClientesCtrl',  ['$scope' , 'reciboClientesSrvc' , 'authSrvc' , '$route' , '$confirm' , '$filter'  , 'DTOptionsBuilder' , 'detContSrvc' , 'cuentacSrvc' , 'clienteSrvc', function($scope , reciboClientesSrvc , authSrvc , $route , $confirm , $filter , DTOptionsBuilder , detContSrvc , cuentacSrvc , clienteSrvc){

        $scope.reccli = {idempresa: 0};
        $scope.reciboscli = [];
        $scope.permiso = {};
        $scope.clientes = [];
        $scope.tranban = [];
        $scope.detreccli = {};
        $scope.lstdetreccli = [];
        $scope.lstdocspend = [];
        $scope.lstdetcont = [];
        $scope.elDetCont = {};
        //Inicio modificacion
        $scope.fltrre = {idempresa:0, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), recibostr:'', clientestr:'', ban_numerostr: '', ban_cuentastr:'', serie:''};
        //Fin modificacion
        $scope.origen = 8;
        $scope.cuentas = [];
        $scope.usr = {};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('fnRowCallback', rowCallback);
        $scope.selected = {}; //Rony 2017-11-16 Editar monto abono

        authSrvc.getSession().then(function(usrLogged){
            $scope.usr = usrLogged;
            if(parseInt(usrLogged.workingon) > 0){
                authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.reccli.idempresa = parseInt(usrLogged.workingon);
                //Inicio modificacion
                //$scope.getLstRecibosCli($scope.reccli.idempresa);
                $scope.getLstRecibosCli();
                //Fin modificacion
                $scope.resetRecCli();
                $scope.loadTranBan($scope.reccli.idempresa);
            }
        });

        clienteSrvc.lstCliente().then(function(d){
            d.push({
                id: "0", nombre: "Facturas contado (Clientes varios)", nombrecorto: "FactCont",
                direntrega: "", dirplanta: null, telpbx: "", teldirecto: "", telfax: null, telcel: "", correo: "", idordencedula: "", regcedula: null, dpi: "",
                cargolegal: "", nomlegal: "", apellidolegal: "", nomadmon: "", mailadmon: "", nompago: "", mailcont: "", idcuentac: "", creadopor: "", fhcreacion: "", actualizadopor: "",
                fhactualizacion: "",contratos: ""
            });
            $scope.clientes = d;
        });

        $scope.resetRecCli = function(){
            $scope.reccli = {
                idempresa: $scope.reccli.idempresa,
                fecha: moment().toDate(),
                idtranban: 0,
                idcliente: 0,
                objTranBan: [],
                objCliente: [],
                espropio: 0,
                serie: undefined,
                numero: undefined,
                usuariocrea: ''
            };
            goTop();
        };

        $scope.loadTranBan = function(idempresa){
            reciboClientesSrvc.lstTranBan(idempresa).then(function(d){
                for(var i = 0; i< d.length; i++){
                    d[i].id = parseInt(d[i].id);
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
                d[i].idcliente = parseInt(d[i].idcliente);
                d[i].espropio = parseInt(d[i].espropio);
                d[i].anulado = parseInt(d[i].anulado);
                d[i].idrazonanulacion = parseInt(d[i].idrazonanulacion);
                d[i].fecha = moment(d[i].fecha).toDate();
                d[i].fechaanula = moment(d[i].fecha).isValid() ? moment(d[i].fecha).toDate() : null;
                d[i].fechacrea = moment(d[i].fechacrea).toDate();
                d[i].numero = parseInt(d[i].numero);
            }
            return d;
        }
//Inicio modificacion
        $scope.getLstRecibosCli = function(){
            $scope.fltrre.idempresa = $scope.reccli.idempresa;
            $scope.fltrre.fdelstr = moment($scope.fltrre.fdel).format('YYYY-MM-DD');
            $scope.fltrre.falstr = moment($scope.fltrre.fal).format('YYYY-MM-DD');
            $scope.fltrre.serie = $scope.fltrre.serie != null && $scope.fltrre.serie != undefined ? $scope.fltrre.serie : '';
            $scope.fltrre.recibostr = $scope.fltrre.recibostr != null && $scope.fltrre.recibostr != undefined ? $scope.fltrre.recibostr : 0;
            $scope.fltrre.clientestr = $scope.fltrre.clientestr != null && $scope.fltrre.clientestr != undefined ? $scope.fltrre.clientestr : '';
            $scope.fltrre.ban_numerostr = $scope.fltrre.ban_numerostr != null && $scope.fltrre.ban_numerostr != undefined ? $scope.fltrre.ban_numerostr : '';
            $scope.fltrre.ban_cuentastr = $scope.fltrre.ban_cuentastr != null && $scope.fltrre.ban_cuentastr != undefined ? $scope.fltrre.ban_cuentastr : '';
            reciboClientesSrvc.lstRecibosClientes($scope.fltrre).then(function(d){
                $scope.reciboscli = procDataRecs(d);
            });
        };
 //Fin Modificacion

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


        $scope.loadDetCont = function(idreccli){
            $scope.lstdetcont = [];
            detContSrvc.lstDetalleCont($scope.origen, idreccli).then(function(d){
                $scope.lstdetcont = procDetCont(d);
            });
        };


        $scope.getRecCli = function(idreccli){
            reciboClientesSrvc.getReciboCliente(idreccli).then(function(d){
                $scope.reccli = procDataRecs(d)[0];
                $scope.reccli.objCliente = [$filter('getById')($scope.clientes, $scope.reccli.idcliente)];
                $scope.reccli.objTranBan = [$filter('getById')($scope.tranban, $scope.reccli.idtranban)];
                $scope.resetDetRecCli();
                $scope.loadDetRecCli(idreccli);
                $scope.loadDocsPend($scope.reccli.idempresa, $scope.reccli.idcliente); //Esta linea actualiza la informacion de facturas pendientes del cliente
                cuentacSrvc.getByTipo($scope.reccli.idempresa, 0).then(function(d){ $scope.cuentas = d; });
                $scope.loadDetCont(idreccli);
                goTop();
            });
        };

        function setRecCliData(obj){
            obj.fechastr = moment(obj.fecha).format('YYYY-MM-DD');
            obj.idcliente = obj.objCliente[0].id;
            obj.espropio = obj.espropio != null && obj.espropio != undefined ? obj.espropio : 0;
            obj.idtranban = obj.objTranBan[0] != null && obj.objTranBan[0] != undefined ? obj.objTranBan[0].id : 0;
            obj.usuariocrea = $scope.usr.usuario;
            return obj;
        }

        $scope.addRecCli = function(obj){
            obj = setRecCliData(obj);
            //console.log(obj); return;
            reciboClientesSrvc.editRow(obj, 'c').then(function(d){
                //Inicio Modificacion
                //$scope.getLstRecibosCli(obj.idempresa);
                $scope.getLstRecibosCli();
                //Fin modificacion
                $scope.getRecCli(parseInt(d.lastid));
            });
        };

        $scope.updRecCli = function(obj){
            obj = setRecCliData(obj);
            //console.log(obj); return;
            reciboClientesSrvc.editRow(obj, 'u').then(function(){
                //Inicio modificacion
                //$scope.getLstRecibosCli(obj.idempresa);
                $scope.getLstRecibosCli();
                //Fin Modificacion
                $scope.getRecCli(parseInt(obj.id));
            });
        };

        $scope.delRecCli = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el recibo de clientes No. ' + $filter('padNumber')(obj.id, 5) + '?', title: 'Eliminar recibo de clientes', ok: 'Sí', cancel: 'No'}).then(function() {
                reciboClientesSrvc.editRow({id: obj.id}, 'd').then(function(){ 
                    //Inicio modificacion
                    //$scope.getLstRecibosCli(obj.idempresa); 
                    $scope.getLstRecibosCli();
                    //Fin Modificacion
                    $scope.resetRecCli(); 
                });
            });
        };

        $scope.resetDetRecCli = function(){
            $scope.detreccli = {
                idrecibocli: $scope.reccli.id > 0 ? $scope.reccli.id : 0,
                idfactura: 0,
                monto: 0.00,
                interes: 0.00,
                esrecprov: 1,
                objDocPend: []
            };
            $scope.fltrVenta = '';
            goTop();
        };

        $scope.loadDocsPend = function(idempresa, idcliente){
            reciboClientesSrvc.lstDocsPend(idempresa, idcliente).then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].id = parseInt(d[i].id);
                    d[i].fecha = moment(d[i].fecha).toDate();
                    d[i].total = parseFloat(parseFloat(d[i].total).toFixed(2));
                    d[i].cobrado = parseFloat(parseFloat(d[i].cobrado).toFixed(2));
                    d[i].saldo = parseFloat(parseFloat(d[i].saldo).toFixed(2));
                }
                $scope.lstdocspend = d;
            });
        };

        function procDetaDetRec(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idfactura = parseInt(d[i].idfactura);
                d[i].idrecibocli = parseInt(d[i].idrecibocli);
                d[i].esrecprov = parseInt(d[i].esrecprov);
                d[i].monto = parseFloat(parseFloat(d[i].monto).toFixed(2));
                d[i].interes = parseFloat(parseFloat(d[i].interes).toFixed(2));
            }
            return d;
        }

        $scope.loadDetRecCli = function(idreccli){
            reciboClientesSrvc.lstDetRecCli(idreccli).then(function(d){
                $scope.lstdetreccli = procDetaDetRec(d);
            });
        };

        $scope.setMontoSugerido = function(){
            if($scope.detreccli.objDocPend != null && $scope.detreccli.objDocPend != undefined){
                $scope.detreccli.monto = $scope.detreccli.objDocPend[0] != null && $scope.detreccli.objDocPend[0] != undefined ? $scope.detreccli.objDocPend[0].saldo : 0.00;
            }else{
                $scope.detreccli.monto = 0.00;
            }
        };

        function setDetRec(obj){
            obj.idrecibocli = $scope.reccli.id;
            obj.idfactura = obj.objDocPend[0].id;
            obj.monto = obj.monto != null && obj.monto != undefined ? obj.monto : 0.00;
            obj.interes = obj.interes != null && obj.interes != undefined ? obj.interes : 0.00;
            return obj;
        }

        $scope.addDetRecCli = function(obj){
            obj = setDetRec(obj);
            //console.log(obj);
            reciboClientesSrvc.editRow(obj, 'cd').then(function(d){
                $scope.loadDetRecCli(obj.idrecibocli);
                $scope.loadDocsPend($scope.reccli.idempresa, $scope.reccli.idcliente);
                $scope.resetDetRecCli();
            });
        };

        ////Rony 2017-11-16 Editar monto abono
        $scope.updateDetRecCli = function(obj){
            //console.log('Update....',obj.id,obj.monto,obj.interes,obj.idrecibocli,obj.idfactura);

            $confirm({text: '¿Seguro(a) de actualizar monto aplicado de este documento?', title: 'Modificación', ok: 'Sí', cancel: 'No'}).then(function() {
                reciboClientesSrvc.editRow({id: obj.id, monto: obj.monto, interes: obj.interes, idfactura: obj.idfactura}, 'ud').then(function(){
                    $scope.loadDetRecCli(obj.idrecibocli);
                    $scope.resetDetRecCli();
                    $scope.loadDocsPend($scope.reccli.idempresa, $scope.reccli.idcliente);
                });
            });
            $scope.reset(obj);
        };        

        //Rony 2017-11-16 Editar monto abono
        $scope.editDetRecCli = function(obj){
                //console.log(obj.id,obj.monto,obj.interes);
                $scope.selected = angular.copy(obj);
        };

        //Rony 2017-11-16 Editar monto abono
        $scope.getTemplate = function (obj) {
            //console.log(obj.id);
            //console.log($scope.selected.id);
            if (obj.id === $scope.selected.id){
                return 'edit';
            }
            else return 'display';
        };

        //Rony 2017-11-16 Editar monto abono
        $scope.reset = function (obj) {
            //console.log('Resset...',$scope.reccli.idempresa,$scope.reccli.idcliente);
            $scope.selected = {};
            $scope.loadDetRecCli(obj.idrecibocli);
            $scope.resetDetRecCli();
            $scope.loadDocsPend($scope.reccli.idempresa, $scope.reccli.idcliente);            
        };               

        $scope.delDetRecCli = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este documento? (Esto dejará como pendiente el documento)', title: 'Eliminar documento rebajado', ok: 'Sí', cancel: 'No'}).then(function() {
                reciboClientesSrvc.editRow({id: obj.id, idfactura: obj.idfactura}, 'dd').then(function(){
                    $scope.loadDetRecCli(obj.idrecibocli);
                    $scope.resetDetRecCli();
                    $scope.loadDocsPend($scope.reccli.idempresa, $scope.reccli.idcliente);
                });
            });
        };

        $scope.zeroDebe = function(valor){ $scope.elDetCont.debe = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.debe; };
        $scope.zeroHaber = function(valor){ $scope.elDetCont.haber = parseFloat(valor) > 0 ? 0.0 : $scope.elDetCont.haber; };

        $scope.addDetCont = function(obj){
            obj.origen = $scope.origen;
            obj.idorigen = parseInt($scope.reccli.id);
            obj.debe = parseFloat(obj.debe);
            obj.haber = parseFloat(obj.haber);
            obj.idcuenta = parseInt(obj.objCuenta[0].id);
            detContSrvc.editRow(obj, 'c').then(function(){
                detContSrvc.lstDetalleCont($scope.origen, $scope.reccli.id).then(function(detc){
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
