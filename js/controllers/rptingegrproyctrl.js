(function(){
    
        var rptingegrproyctrl = angular.module('cpm.rptingegrproyctrl', []);
    
        rptingegrproyctrl.controller('rptIngresosEgresosProyCtrl', ['$scope', 'rptIngresosEgresosProySrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', '$window', 'jsReportSrvc', function($scope, rptIngresosEgresosProySrvc, authSrvc, empresaSrvc, proyectoSrvc, $window, jsReportSrvc){
    
            $scope.params = { mes: (moment().month() + 1).toString(), anio: moment().year(), idempresa: undefined, idproyecto: undefined };
            $scope.datos = undefined;
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.datosdet = undefined;

            empresaSrvc.lstEmpresas().then(function(d){
                $scope.empresas = d;
                authSrvc.getSession().then(function(usrLogged){
                    if(usrLogged.workingon > 0){
                        $scope.params.idempresa = usrLogged.workingon.toString();
                        $scope.loadProyectos($scope.params.idempresa);
                    }
                });
            });

            $scope.loadProyectos = function(idempresa){ proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then(function(d){ $scope.proyectos = d; }); };

            $scope.getResumen = function(){
                $scope.datosdet = undefined;
                rptIngresosEgresosProySrvc.resumen($scope.params).then(function(d){ $scope.datos = d; });
            };

            $scope.getResumenPDF = function(){
                $scope.datosdet = undefined;
                var test = false;
                jsReportSrvc.getPDFReport(test ? '' : 'SkIr8bcjW', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };

            $scope.getDetalle = function(){
                $scope.datos = undefined;
                rptIngresosEgresosProySrvc.detalle($scope.params).then(function(d){
                    $scope.datosdet = d;
                    //console.log($scope.datosdet);
                });
            };

            $scope.getDetallePDF = function(){
                $scope.datos = undefined;
                var test = false;
                jsReportSrvc.getPDFReport(test ? 'Hkd2m5q1z' : 'Hkd2m5q1z', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };
    
        }]);
    
    }());