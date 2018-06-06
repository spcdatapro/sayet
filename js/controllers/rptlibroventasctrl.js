(function(){

    var rptlibventactrl = angular.module('cpm.rptlibventactrl', []);

    rptlibventactrl.controller('rptLibroVentasCtrl', ['$scope', 'rptLibroVentaSrvc', 'authSrvc', 'empresaSrvc','$sce', 'jsReportSrvc', 'ventaSrvc', function($scope, rptLibroVentaSrvc, authSrvc, empresaSrvc, $sce, jsReportSrvc, ventaSrvc){

        $scope.params = {
            mes: (moment().month() + 1).toString(), anio: moment().year(), idempresa: 0, sinret: 0, resumen: 0, parqueo: 0.00, retenido: 0.00, alfa:0, cliente: undefined,
            fdel: undefined, fal: undefined
        };
        $scope.libroventas = [];
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $scope.totiva = {activo: 0.0, bien: 0.0, servicio: 0.0};
        $scope.empresa = { nomempresa: '', nit: '' };
        $scope.clientes = [];

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        ventaSrvc.lstClientes().then(function(d){ $scope.clientes = d; });

        $scope.resetData = function(){
            $scope.libroventas = [];
            $scope.totiva = {activo: 0.0, bien: 0.0, servicio: 0.0};
        };

        $scope.getLibVenta = function(){
            $scope.params.alfa = $scope.params.alfa != null && $scope.params.alfa != undefined ? $scope.params.alfa : 0;
			jsReportSrvc.libroventas($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
                });
        };

        var test = false;
        $scope.getLibroVentasExcel = function(){
            $scope.params.alfa = $scope.params.alfa != null && $scope.params.alfa != undefined ? $scope.params.alfa : 0;
            jsReportSrvc.getReport(test ? 'SyJpY4JFz' : 'Hk5Y04yKz', $scope.params).then(function (result) {
                var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var nombre = $scope.meses[$scope.params.mes - 1] + '_' + $scope.params.anio;
                saveAs(file, 'LibroVentas_' + nombre + '.xlsx');
            });
        };
		
		$scope.getLibIsr = function(){
			$scope.params.resumen = 0;
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
            $scope.params.fdelstr = $scope.params.fdel != null && $scope.params.fdel != undefined && moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = $scope.params.fal != null && $scope.params.fal != undefined && moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
			
			jsReportSrvc.libroisr($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
            });
        };
		
		$scope.getLibIsrRes = function(){
			$scope.params.resumen = 1;
			$scope.params.sinret = 0;
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente != undefined ? $scope.params.cliente : '';
			
			jsReportSrvc.libroisrres($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
            });
        };
		
        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Libro de ventas');
        };

    }]);

}());
