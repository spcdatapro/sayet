(function(){

    var gfacectrl = angular.module('cpm.gfacectrl', []);

    gfacectrl.controller('gfaceCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', '$filter', '$confirm', 'facturacionSrvc', '$window', 'toaster', function($scope, empresaSrvc, jsReportSrvc, authSrvc, $filter, $confirm, facturacionSrvc, $window, toaster){

        $scope.params = {idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate()};
        $scope.empresas = [];

        authSrvc.getSession().then(function(usrLogged){
            empresaSrvc.lstEmpresas().then(function(d){
                $scope.empresas = d;
                $scope.params.idempresa = usrLogged.workingon.toString();
            });
        });


        var test = false;
        $scope.getGFACE = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            var abreviatura = $filter('getById')($scope.empresas, $scope.params.idempresa).abreviatura, nombre = '';
            abreviatura = abreviatura != null && abreviatura != undefined ? abreviatura : '';
            nombre = abreviatura + '-GFACE' + moment().format('DDMMYYYYhhmmss');
            var qstr = $scope.params.idempresa + '/' + $scope.params.fdelstr + '/' + $scope.params.falstr + '/' + nombre;
            $window.open('php/facturacion.php/gettxt/' + qstr);            
        };

        $scope.resetParams = function(){ $scope.params = { idempresa: $scope.params.idempresa, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate()}; };

        $scope.showContent = function($fileContent){
            $scope.content = $fileContent;
            $confirm({text: '¿Desea actualizar las facturas con estos datos?', title: 'Archivo de respuesta de GFACE', ok: 'Sí', cancel: 'No'}).then(function() {
                $scope.procesaArchivo($scope.content);
            });
        };

        $scope.procesaArchivo = function(archivo){
            //var cadena = archivo.split('\r\n');
            var cadena = archivo.split('\n');
            //console.log(cadena); return;
            var linea, facturas = [];
            cadena.forEach(function(cad){
                linea = cad.replace('\r', '').replace('\n', '').split('|');
                //console.log(linea);
                //facturas.push({ id: +linea[9], firma: linea[8], serie: linea[1], numero: linea[2], nit: linea[3], nombre: linea[4], respuesta: cad });
                facturas.push({ id: +linea[8], firma: linea[7], serie: linea[2], numero: linea[3], nit: linea[4], nombre: linea[9], respuesta: cad });
            });
            //console.log(facturas); return;
            if(facturas.length > 0){
                facturacionSrvc.respuestaGFACE(facturas).then(function(d){
                    //console.log(d.estatus);
                    toaster.pop({ type: 'success', title: 'Proceso terminado', body: 'Las facturas fueron actualizadas con su firma.', timeout: 9000 });
                    $scope.content = '';
                    $('#txtFile').val(undefined);
                })
            }
        };

        $scope.getRptPendientes = function(){
            jsReportSrvc.getPDFReport(test ? 'S1wR9_Mif' : 'HyJCJizjf', $scope.params).then(function(pdf){ $scope.content = pdf; });
        }

    }]);
}());

