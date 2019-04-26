(function(){
    
        var rptingegrproyctrl = angular.module('cpm.rptingegrproyctrl', []);
    
        rptingegrproyctrl.controller('rptIngresosEgresosProyCtrl', ['$scope', 'rptIngresosEgresosProySrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', '$window', 'jsReportSrvc', function($scope, rptIngresosEgresosProySrvc, authSrvc, empresaSrvc, proyectoSrvc, $window, jsReportSrvc){
    
            $scope.params = {
                mes: (moment().month() + 1).toString(), anio: moment().year(), idempresa: undefined, idproyecto: undefined, dmes: (moment().month() + 1).toString(),
                ames: (moment().month() + 1).toString()
            };
            //$scope.datos = undefined;
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.datosdet = undefined;
            $scope.rangeData = [];
            $scope.rangeDataDetalle = [];

            //$scope.$watch('params', function(newValue, oldValue){ });

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

            $scope.getResumen = async function(){
                $scope.rangeData = [];
                $scope.rangeDataDetalle = [];
                $scope.datosdet = undefined;

                var ames = +$scope.params.ames;
                var data = [];

                for(var i = +$scope.params.dmes; i <= ames; i++){
                    $scope.params.mes = i;
                    data = await rptIngresosEgresosProySrvc.resumen($scope.params);
                    $scope.rangeData.push({
                        nomproyecto: data.proyecto.nomproyecto,
                        referencia: data.proyecto.referencia,
                        empresa: data.proyecto.empresa,
                        abreviaempresa: data.proyecto.abreviaempresa,
                        anio: data.proyecto.anio,
                        mes: data.proyecto.mes,
                        datos: data
                    });
                }
                $scope.$digest();
            }

            $scope.getResumenPDF = function(){
                $scope.datosdet = undefined;
                var test = false;
                jsReportSrvc.getPDFReport(test ? '' : 'SkIr8bcjW', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };

            $scope.getDetalle = async function(){
                $scope.rangeDataDetalle = [];
                $scope.rangeData = [];

                var ames = +$scope.params.ames;
                var data = [];

                for(var i = +$scope.params.dmes; i <= ames; i++){
                    $scope.params.mes = i;
                    data = await rptIngresosEgresosProySrvc.detalle($scope.params);
                    $scope.rangeDataDetalle.push({
                        nomproyecto: data.proyecto.nomproyecto,
                        referencia: data.proyecto.referencia,
                        empresa: data.proyecto.empresa,
                        abreviaempresa: data.proyecto.abreviaempresa,
                        anio: data.proyecto.anio,
                        mes: data.proyecto.mes,
                        datos: data
                    });
                }
                $scope.$digest();
            }

            $scope.getDetallePDF = function(){
                //$scope.datos = undefined;
                var test = false;
                jsReportSrvc.getPDFReport(test ? 'Hkd2m5q1z' : 'Hkd2m5q1z', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };
    
        }]);
    
    }());