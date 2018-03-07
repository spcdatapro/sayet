(function(){

    var rptfactprovctrl = angular.module('cpm.rptfactprovctrl', ['cpm.tranbacsrvc']);

    rptfactprovctrl.controller('rptFactProvCtrl', ['$scope', 'tranPagosSrvc', 'authSrvc', 'proveedorSrvc', 'empresaSrvc', function($scope, tranPagosSrvc, authSrvc, proveedorSrvc, empresaSrvc){

        $scope.objEmpresa = {};
        $scope.losProvs = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idprov: 0,
            pendientes: 1, fdelstr: '', falstr:'' };
        $scope.lasFact = [];
        $scope.objProv = [];
        $scope.losPagos = [];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.params.idempresa = parseInt($scope.objEmpresa.id);
                });
            }
        });

        proveedorSrvc.lstProveedores().then(function(d) {
            $scope.losProvs = d;
            $scope.losProvs.push({id:0, nitnombre: 'Todos los proveedores'});
        });

        $scope.getFactProv = function(){
            $scope.params.idprov = $scope.objProv[0] !== null && $scope.objProv[0] !== undefined ? ($scope.objProv.length == 1 ? $scope.objProv[0].id : 0) : 0;
            $scope.params.pendientes = $scope.params.pendientes !== null && $scope.params.pendientes !== undefined ? $scope.params.pendientes : 0;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            tranPagosSrvc.rptfactprov($scope.params).then(function(d){
                $scope.lasFact = d;
                $scope.styleData();
            });
        };

        function lookFor(aBuscar, enQueBuscar){
            var hallado = false;
            for(var x = 0; x < enQueBuscar.length; x++){
                if(parseInt(enQueBuscar[x].idproveedor) === parseInt(aBuscar)){
                    hallado = true;
                    break;
                }
            }
            return hallado;
        };

        $scope.styleData = function(){
            $scope.losPagos = [];
            if($scope.objProv.length == 0){ $scope.objProv = $scope.losProvs; };
            if(($scope.objProv.length == 1 && $scope.objProv[0].id == 0) || $scope.objProv.length > 1){ $scope.objProv = $scope.losProvs; };

            var tmp = {};
            var tmpf = {};
            var ev = '';
            var sumProveedor = {totFact: 0.0, totSaldo: 0.0};
            var sumGeneral = {totFact: 0.0, totSaldo: 0.0};
            for(var i = 0; i < $scope.objProv.length; i++){
                tmp = $scope.objProv[i];                
                if(tmp.id != 0 && lookFor(tmp.id, $scope.lasFact)){
                    sumProveedor = {totFact: 0.0, totSaldo: 0.0};
                    $scope.losPagos.push({
                        nit: tmp.nit, proveedor:tmp.nombre, serie:ev, documento:ev, fechaingreso:ev, fechapago:ev,conceptomayor:ev,totfact:ev,saldo:ev
                    });

                    for(var j = 0; j < $scope.lasFact.length; j++){
                        tmpf = $scope.lasFact[j];
                        if(parseInt(tmpf.idproveedor) === parseInt(tmp.id)){
                            $scope.losPagos.push({
                                nit: ev, proveedor:ev, serie:tmpf.serie, documento:tmpf.documento, fechaingreso:tmpf.fechaingreso,
                                fechapago:tmpf.fechapago,conceptomayor:tmpf.conceptomayor,totfact:parseFloat(tmpf.totfact),saldo:parseFloat(tmpf.saldo)
                            });
                            sumProveedor.totFact += parseFloat(tmpf.totfact);
                            sumProveedor.totSaldo += parseFloat(tmpf.saldo);
                        }
                    };

                    $scope.losPagos.push({
                        nit: ev, proveedor:ev, serie:ev, documento:'Subtotal', fechaingreso:ev, fechapago:ev,
                        conceptomayor: '--->', totfact: sumProveedor.totFact, saldo: sumProveedor.totSaldo
                    });

                    sumGeneral.totFact += sumProveedor.totFact;
                    sumGeneral.totSaldo += sumProveedor.totSaldo;
                };
            };

            $scope.losPagos.push({
                nit: ev, proveedor:ev, serie:ev, documento:'Total general', fechaingreso:ev, fechapago:ev,
                conceptomayor: '--->', totfact: sumGeneral.totFact, saldo:sumGeneral.totSaldo
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Facturas de proveedores');
        };

    }]);

}());
