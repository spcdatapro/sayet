(function(){

    const trangenchqotsctrl = angular.module('cpm.trangenchqotsctrl', []);

    trangenchqotsctrl.controller('tranGenChqOtsCtrl', ['$scope', 'presupuestoSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'toaster', 'periodoContableSrvc', function($scope, presupuestoSrvc, authSrvc, bancoSrvc, empresaSrvc, toaster, periodoContableSrvc){

        $scope.objEmpresa = {};
        $scope.losPagos = [];
        $scope.losBancos = [];
        $scope.objBanco = {};
        $scope.qpagos = [];
        $scope.totales = {cantots: 0, monto: 0.00};
        $scope.periodoCerrado = false;
        $scope.fechatran = moment().toDate();
        $scope.empresas = [];
        $scope.generarTodos = 1;

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.objEmpresa = d[0];
                    $scope.getPagos();
                    $scope.loadBancos();
                });
            }
        });

        $scope.loadBancos = function(){
            bancoSrvc.lstBancosActivos(null).then(function(d){ $scope.losBancos = d; });
        };

        function procDataPagos(data){
            for(let i = 0; i < data.length; i++){
                data[i].id = +data[i].id;
                data[i].generar = +data[i].generar;
            }
            return data;
        }

        $scope.$watch('fechatran', function(newValue, oldValue){
            if(newValue != null && newValue !== undefined){
                $scope.chkFechaEnPeriodo(newValue);
            }
        });

        $scope.chkFechaEnPeriodo = function(qFecha){
            if(angular.isDate(qFecha)){
                if(qFecha.getFullYear() >= 2000){
                    periodoContableSrvc.validaFecha(moment(qFecha).format('YYYY-MM-DD')).then(function(d){
                        const fechaValida = parseInt(d.valida) === 1;
                        if(!fechaValida){
                            $scope.periodoCerrado = true;
                            toaster.pop({ type: 'error', title: 'Fecha de transacción inválida.',
                                body: 'No está dentro de ningún período contable abierto.', timeout: 7000 });
                        } else {
                            $scope.periodoCerrado = false;
                        }
                    });
                }
            }
        };

        $scope.getPagos = function(){
            presupuestoSrvc.lstPagosPendOt().then(function(d){
                $scope.empresas = d.empresas;
                $scope.losPagos = procDataPagos(d.pagos);
            });
        };

        $scope.setBanco = (idempresa, idbanco) => $scope.losPagos.map((p) => p.idbanco = +p.idempresa === +idempresa ? idbanco : p.idbanco);
        $scope.setGenerar = () => $scope.losPagos.map((p) => p.generar = $scope.generarTodos);

        $scope.generaCheques = () => {
            let pago;
            for(let i = 0; i < $scope.losPagos.length; i++){
                pago = $scope.losPagos[i];
                if(+pago.generar === 1 && +pago.idbanco > 0){
                    $scope.qpagos.push({
                        idpago: pago.iddetpagopresup,
                        idbanco: pago.idbanco
                    });
                }
            }
            if($scope.qpagos.length > 0){
                presupuestoSrvc.editRow({
                    fecha: moment($scope.fechatran).format('YYYY-MM-DD'),
                    pagos: $scope.qpagos
                }, 'genpagos').then((d)=>{
                    if(d.segeneraron) {
                        toaster.pop({
                            type: 'success',
                            title: 'Generación de pagos de OTs',
                            body: `Se generaron los cheques No. ${d.cheques}`,
                            timeout: 3000
                        });
                    }else{
                        toaster.pop({
                            type: 'error',
                            title: 'Generación de pagos de OTs',
                            body: 'Hubo un error en la generación de cheques.',
                            timeout: 3000
                        });
                    }
                    $scope.qpagos = [];
                    $scope.getPagos();
                    $scope.loadBancos();
                });
            }
        };

    }]);

}());
