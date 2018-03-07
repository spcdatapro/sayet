(function(){

    var tranpagosctrl = angular.module('cpm.tranpagosctrl', ['cpm.tranbacsrvc']);

    tranpagosctrl.controller('tranPagosCtrl', ['$scope', 'tranPagosSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', 'toaster', function($scope, tranPagosSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder, toaster){

        $scope.objEmpresa = {};
        $scope.losPagos = [];
        $scope.feclimite = moment().toDate();
        $scope.fechatran = moment().toDate();
        $scope.losBancos = [];
        $scope.objBanco = {};
        $scope.esperando = false;
        $scope.qpagos = [];
        $scope.totales = {cantfacts: 0, monto: 0.00};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('responsive', true).withOption('ordering', false).withOption('paging', false);

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.getPagos($scope.objEmpresa.id, null);
                    $scope.loadBancos();
                });
            }
        });

        $scope.loadBancos = function(){
            bancoSrvc.lstBancosActivos($scope.objEmpresa.id).then(function(d){ $scope.losBancos = d; });
        };

        function procDataPagos(data){
            for(var i = 0; i < data.length; i++){
                data[i].id = parseInt(data[i].id);
                data[i].idempresa = parseInt(data[i].idempresa);
                data[i].idproveedor = parseInt(data[i].idproveedor);
                data[i].documento = parseInt(data[i].documento);
                data[i].fechapago = moment(data[i].fechapago).toDate();
                data[i].subtotal = parseFloat(data[i].subtotal);
                data[i].totfact = parseFloat(data[i].totfact);
                data[i].montopagado = parseFloat(data[i].montopagado);
                data[i].retenisr = parseInt(data[i].retenisr);
                data[i].pagatodo = parseInt(data[i].pagatodo);
                data[i].montoapagar = parseFloat(data[i].montoapagar);
                data[i].saldo = parseFloat(data[i].saldo);
                data[i].pagar = parseInt(data[i].pagar);
                data[i].isr = parseFloat(data[i].isr);
                data[i].tipocambio = parseFloat(data[i].tipocambio);
                data[i].idmoneda = parseInt(data[i].idmoneda);
            }
            return data;
        }

        $scope.getPagos = function(idempresa, bco){
            var fmoneda = 1;
            if(bco != null && bco != undefined){
                //fmoneda = parseFloat(bco.tipocambio) > 1 ? parseInt(bco.idmoneda) : 0;
                fmoneda = parseInt(bco.idmoneda);
            }
            tranPagosSrvc.lstPagos(idempresa, moment($scope.feclimite).format('YYYY-MM-DD'), fmoneda).then(function(d){ $scope.losPagos = procDataPagos(d); });
        };

        $scope.setMontoAPagar = function(obj){
            if(obj.pagatodo === 1){
                obj.montoapagar = obj.saldo;
            }
        };

        $scope.chkMontoAPagar = function(obj){
            if(obj.montoapagar <= 0 || obj.montoapagar > obj.saldo){
                obj.montoapagar = obj.saldo;
                toaster.pop({ type: 'error', title: 'Error en el monto a pagar. Factura ' + obj.serie + ' ' + obj.documento + '.',
                    body: 'El monto a pagar no puede ser cero (0) ni mayor a ' + obj.saldo.toFixed(2), timeout: 7000 });
            }
        };

        $scope.refrescarInfo = function(){
            $scope.totales = {cantfacts: 0, monto: 0.00};
            for(var i = 0; i < $scope.losPagos.length; i++){
                if(+$scope.losPagos[i].pagar === 1){
                    $scope.totales.cantfacts++;
                    $scope.totales.monto += parseFloat(parseFloat($scope.losPagos[i].montoapagar).toFixed(2));
                }
            }
        };

        $scope.generaCheques = function(){
            $scope.esperando = true;
            $scope.qpagos = [];
            $scope.qpagos.push({
                idbanco: parseInt($scope.objBanco.id),
                nombanco: $scope.objBanco.nombre,
                idmoneda: parseInt($scope.objBanco.idmoneda),
                tipocambio: parseFloat($scope.objBanco.tipocambio),
                fechatranstr: moment($scope.fechatran).format('YYYY-MM-DD')
            });
            for(var i = 0; i < $scope.losPagos.length; i++){
                if($scope.losPagos[i].pagar === 1){
                    $scope.losPagos[i].fechapagostr = moment($scope.losPagos[i].fechapago).format('YYYY-MM-DD');
                    $scope.qpagos.push($scope.losPagos[i]);
                }
            }
            if($scope.qpagos.length > 1){
                tranPagosSrvc.genPagos($scope.qpagos).then(function(d){
                    $scope.esperando = false;
                    $scope.qpagos = [];
                    $scope.getPagos($scope.objEmpresa.id, $scope.objBanco);
                    $scope.loadBancos();
                    toaster.pop({ type: 'info', title: 'Cheques generados', body: d.mensaje, timeout: 7000 });
                });
            }else{
                $scope.esperando = false;
                toaster.pop({ type: 'info', title: 'Información',
                    body: 'Para poder generar cheques, seleccione una factura con saldo pendiente, por favor.', timeout: 7000 });
            }

        };
    }]);

}());
