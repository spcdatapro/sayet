(function(){

    var rpthistpagosctrl = angular.module('cpm.rpthistpagosctrl', []);

    rpthistpagosctrl.controller('rptHistPagosCtrl', ['$scope', 'tranPagosSrvc', 'authSrvc', 'proveedorSrvc', 'empresaSrvc', function($scope, tranPagosSrvc, authSrvc, proveedorSrvc, empresaSrvc){

        $scope.objEmpresa = {};
        $scope.losProvs = [];
        $scope.params = { idempresa: 0, fDel: moment().startOf('month').toDate(), fAl: moment().endOf('month').toDate(), idprov: 0,
            fdelstr: '', falstr:'' };
        $scope.losPagos = [];
        $scope.objProv = [];
        $scope.data = [];

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

        $scope.getHistPagos = function(){
            $scope.params.idprov = $scope.objProv[0] !== null && $scope.objProv[0] !== undefined ? ($scope.objProv.length == 1 ? $scope.objProv[0].id : 0) : 0;
            //$scope.params.serie = $scope.params.serie !== null && $scope.params.serie !== undefined ? $scope.params.serie : '';
            //$scope.params.documento = $scope.params.documento !== null && $scope.params.documento !== undefined ? $scope.params.documento : 0;
            $scope.params.fdelstr = moment($scope.params.fDel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fAl).format('YYYY-MM-DD');
            tranPagosSrvc.rpthistpagos($scope.params).then(function(d){
                $scope.losPagos = d;
                //console.log($scope.losPagos);
                $scope.styleData();
            });
        };



        function indexOfProv(myArray, searchTerm) {
            var index = -1;
            for(var i = 0, len = myArray.length; i < len; i++) {
                if (myArray[i].idprov === searchTerm) {
                    index = i;
                    break;
                }
            }
            return index;
        };

        function indexOfCompra(myArray, searchTerm) {
            var index = -1;
            for(var i = 0, len = myArray.length; i < len; i++) {
                if (myArray[i].idcompra === searchTerm) {
                    index = i;
                    break;
                }
            }
            return index;
        };

        function getProvs(){
            var uniqueProvs = [];
            for(var x = 0; x < $scope.losPagos.length; x++){
                if(indexOfProv (uniqueProvs, parseInt($scope.losPagos[x].idprov)) < 0){
                    uniqueProvs.push({
                        idprov: parseInt($scope.losPagos[x].idprov),
                        nit: $scope.losPagos[x].nit,
                        nombre: $scope.losPagos[x].proveedor
                    });
                };
            };
            return uniqueProvs;
        };

        function getCompras(){
            var uniqueCompras = [];
            for(var x = 0; x < $scope.losPagos.length; x++){
                if(indexOfCompra(uniqueCompras, parseInt($scope.losPagos[x].idcompra)) < 0){
                    uniqueCompras.push({
                        idprov: parseInt($scope.losPagos[x].idprov),
                        idcompra: parseInt($scope.losPagos[x].idcompra),
                        documento: $scope.losPagos[x].documento,
                        totfact: parseFloat($scope.losPagos[x].totfact)
                    });
                };
            };
            return uniqueCompras;
        };

        $scope.styleData = function(){
            $scope.data = [];
            var qProvs = getProvs();
            var qCompras = getCompras();
            var tmp = {};
            var sumas = {totPagado: 0.0};

            for(var i = 0; i < qProvs.length; i++){
                $scope.data.push({
                    idprov: qProvs[i].idprov,
                    nit: qProvs[i].nit,
                    nombre: qProvs[i].nombre,
                    facturas: []
                });
            };
            for(var i = 0; i < $scope.data.length; i++){
                for(var j = 0; j < qCompras.length; j++){
                    if(qCompras[j].idprov === $scope.data[i].idprov){
                        $scope.data[i].facturas.push({
                            idcompra: qCompras[j].idcompra,
                            documento: qCompras[j].documento,
                            totfact: parseFloat(qCompras[j].totfact),
                            detpago: []
                        });
                    };
                };
            };

            for(var i = 0; i < $scope.data.length; i++){
                for(var j = 0; j < $scope.data[i].facturas.length; j++){
                    var sumas = {totPagado: 0.0};
                    for(var k = 0; k < $scope.losPagos.length; k++){
                        tmp = $scope.losPagos[k];
                        if(parseInt(tmp.idcompra) === $scope.data[i].facturas[j].idcompra){
                            $scope.data[i].facturas[j].detpago.push({
                                tipotranban: tmp.tipotranban,
                                numero: parseInt(tmp.numero),
                                banco: tmp.banco,
                                fecha: moment(tmp.fecha).toDate(),
                                beneficiario: tmp.beneficiario,
                                monto: parseFloat(tmp.monto)
                            });
                            sumas.totPagado += parseFloat(tmp.monto);
                        };
                    };
                    $scope.data[i].facturas[j].detpago.push({
                        tipotranban: '',
                        numero: '',
                        banco: 'Total pagado',
                        fecha: '',
                        beneficiario: '--->',
                        monto: sumas.totPagado
                    });
                    if(($scope.data[i].facturas[j].totfact - sumas.totPagado) > 0){
                        $scope.data[i].facturas[j].detpago.push({
                            tipotranban: '',
                            numero: '',
                            banco: 'Saldo',
                            fecha: '',
                            beneficiario: '--->',
                            monto: $scope.data[i].facturas[j].totfact - sumas.totPagado
                        });
                    };
                };
            };
            //console.log($scope.data);
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Historial de pagos');
        };

    }]);

}());
