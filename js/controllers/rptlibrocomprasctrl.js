(function(){

    var rptlibcompctrl = angular.module('cpm.rptlibcompctrl', []);

    rptlibcompctrl.controller('rptLibroComprasCtrl', ['$scope', 'rptLibroComprasSrvc', 'authSrvc', 'empresaSrvc','$sce', 'jsReportSrvc', function($scope, rptLibroComprasSrvc, authSrvc, empresaSrvc, $sce, jsReportSrvc){

        $scope.params = {
            mes: (moment().month() + 1).toString(), anio: moment().year(), idempresa: 0, del: '', al: '', orden:1, creditofiscal: 1,
            montoactivo: 0.00, refactivo: '' 
        };
        $scope.librocompras = [];
        $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $scope.totiva = {combustible: 0.0, bien: 0.0, servicio: 0.0, importaciones: 0.0};
        $scope.empresa = { nomempresa: '', nit: '' };
		$scope.content = undefined;

        $scope.getGastosActivo = function(){
            rptLibroComprasSrvc.getGastosActivo($scope.params.idempresa, $scope.params.mes, $scope.params.anio).then(function(d){
                if(parseFloat(d.gastosactivo)){
                    $scope.params.montoactivo = parseFloat(d.gastosactivo);
                } else {
                    $scope.params.montoactivo = 0.00;
                }
            });
        };

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                //authSrvc.gpr({idusuario: parseInt(usrLogged.uid), ruta:$route.current.params.name}).then(function(d){ $scope.permiso = d; });
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
                $scope.getGastosActivo();
            }
        });

        $scope.resetData = function(){
            $scope.librocompras = [];
            $scope.totiva = {combustible: 0.0, bien: 0.0, servicio: 0.0, importaciones: 0.0};
        };

        $scope.getLibComp = function(){
			$scope.params.del = moment($scope.params.del).isValid() ? moment($scope.params.del).format('YYYY-MM-DD') : '';
            $scope.params.al = moment($scope.params.al).isValid() ? moment($scope.params.al).format('YYYY-MM-DD') : '';
            $scope.params.creditofiscal = $scope.params.creditofiscal != null && $scope.params.creditofiscal != undefined ? $scope.params.creditofiscal : 0;
            $scope.params.montoactivo = $scope.params.montoactivo != null && $scope.params.montoactivo != undefined ? $scope.params.montoactivo : 0.00;
            $scope.params.refactivo = $scope.params.refactivo != null && $scope.params.refactivo != undefined ? $scope.params.refactivo : '';
					
            jsReportSrvc.librocompras($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/pdf'});
                    var fileURL = URL.createObjectURL(file);
                    $scope.content = $sce.trustAsResourceUrl(fileURL);
                });
        };

        $scope.rptIntegraGastosActivo = function(){
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'HJbOVt6xQ' : 'rJP5-9pxQ', {
                mes: $scope.params.mes, anio: $scope.params.anio, idempresa: $scope.params.idempresa
            }).then(function(pdf){
                $scope.content = pdf;
            });
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Libro de compras');
        };

    }]);

}());
