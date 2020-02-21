(function(){
    
        var rptingegrproyctrl = angular.module('cpm.rptingegrproyctrl', []);
    
        rptingegrproyctrl.controller('rptIngresosEgresosProyCtrl', ['$scope', 'rptIngresosEgresosProySrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', '$window', 'jsReportSrvc', '$filter', function($scope, rptIngresosEgresosProySrvc, authSrvc, empresaSrvc, proyectoSrvc, $window, jsReportSrvc, $filter){
    
            /*
            $scope.params = {
                mes: (moment().month() + 1).toString(), anio: moment().year(), idempresa: undefined, idproyecto: undefined, dmes: (moment().month() + 1).toString(),
                ames: (moment().month() + 1).toString(), idunidad: undefined, detallado: 1
            };
            */

            $scope.params = { mes: '1', anio: 2019, idempresa: undefined, idproyecto: undefined, dmes: '1', ames: '3', idunidad: undefined, detallado: 1 }; //Para pruebas...
            //$scope.datos = undefined;
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.unidades = [];
            $scope.datosdet = undefined;
            $scope.rangeData = [];
            $scope.rangeDataDetalle = [];
            $scope.columnar = {};
            $scope.verColumnar = false;
            $scope.info = null;

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

            $scope.loadProyectos = (idempresa) => proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then((d) => $scope.proyectos = d); 

            $scope.loadUnidadesProyecto = (idproyecto) => proyectoSrvc.lstUnidadesProyecto(+idproyecto).then((d) => $scope.unidades = d);

            $scope.getResumen = async function(){
                $scope.rangeData = [];
                $scope.rangeDataDetalle = [];
                $scope.datosdet = undefined;
                $scope.verColumnar = false;

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
                $scope.verColumnar = false;
                var test = false;
                jsReportSrvc.getPDFReport(test ? '' : 'SkIr8bcjW', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };

            $scope.getDetalle = async function(){
                $scope.rangeDataDetalle = [];
                $scope.rangeData = [];
                $scope.verColumnar = false;

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
                $scope.verColumnar = false;
                var test = false;
                jsReportSrvc.getPDFReport(test ? 'Hkd2m5q1z' : 'Hkd2m5q1z', $scope.params).then(function(pdf){
                    $window.open(pdf);
                });
            };

            $scope.getExcel = function(toExport){
                var fileType = "application/vnd.ms-excel; charset=UTF-8";
                var fileName = "IngEgProyecto_" + moment().format('YYYYMMDDHHmmss') + ".xls";
                var info = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
                info = info + '<head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + fileName + '</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
                info = info + '<body>' + document.getElementById(toExport).innerHTML + '</body>';
                info = info + '</html>';

                var blob = new Blob([info], { encoding:"UTF-8", type: fileType });
                saveAs(blob, fileName);
            };

            $scope.formatColumnar = () => {
                if($scope.rangeData.length > 0){
                    doColumnar($scope.rangeData);
                } else if($scope.rangeDataDetalle.length > 0){
                    doColumnar($scope.rangeDataDetalle);
                }
            };

            existeConcepto = (needle, haystack, columna, from) => {
                let existe = -1;
                for(let i = from; i < haystack.length; i++){
                    if(haystack[i][columna].trim().toLowerCase() === needle.trim().toLowerCase()){
                        existe = i;
                        break;
                    }
                }
                return existe;
            };

            doColumnar = (data) => {
                //console.log(data);
                $scope.columnar = {
                    nomproyecto: data[0].nomproyecto,
                    referencia: data[0].referencia,
                    empresa: data[0].empresa,
                    abreviaempresa: data[0].abreviaempresa,
                    anio: data[0].anio,
                    datos: []
                };
                $scope.columnar.datos.push(['CONCEPTO']);
                for(let i = 0; i < data.length; i++){
                    let item = data[i].datos;
                    $scope.columnar.datos[0].push(item.proyecto.mes);
                }
                const columnas = $scope.columnar.datos[0].length;

                let vacios = Array(columnas - 1).fill('');
                //$scope.columnar.datos.push(['INGRESOS']);
                //$scope.columnar.datos[1] = $scope.columnar.datos[1].concat(vacios);
                let mtrxIngresos = [];
                let mtrxEgresos = [];

                let lineaTotalIngresos = ['TOTAL DE INGRESOS'].concat(vacios);
                let lineaTotalEgresos = ['TOTAL DE EGRESOS'].concat(vacios);
                let idxLineaEgresos = -1;

                for(let i = 0; i < data.length; i++){
                    let item = data[i].datos;
                    let ingresos = item.ingresos;
                    let egresos = item.egresos;

                    for(let j = 0; j < ingresos.length; j++){
                        ingreso = ingresos[j];
                        if(ingreso.concepto.toLowerCase().trim().indexOf('total de') < 0){
                            let idxConcepto = existeConcepto(ingreso.concepto, mtrxIngresos, 0, 0);
                            if(idxConcepto < 0){
                                mtrxIngresos.push([ingreso.concepto.toUpperCase()].concat(vacios));
                                idxConcepto = mtrxIngresos.length - 1;
                            }
                            mtrxIngresos[idxConcepto][i + 1] = parseFloat(ingreso.monto) || '';
                        } else {
                            lineaTotalIngresos[i + 1] = parseFloat(ingreso.monto) || '';
                        }
                    }

                    for(let j = 0; j < egresos.length; j++){
                        egreso = egresos[j];
                        if(egreso.concepto.toLowerCase().trim().indexOf('total de') < 0){
                            let idxConcepto = existeConcepto(egreso.concepto, mtrxEgresos, 0, 0);
                            if(idxConcepto < 0){
                                mtrxEgresos.push([egreso.concepto.toUpperCase()].concat(vacios));
                                idxConcepto = mtrxEgresos.length - 1;
                            }
                            mtrxEgresos[idxConcepto][i + 1] = parseFloat(egreso.monto) || '';
                        } else {
                            lineaTotalEgresos[i + 1] = parseFloat(egreso.monto) || '';
                        }
                    }
                }
                $scope.columnar.datos.push(['INGRESOS'].concat(vacios));
                $scope.columnar.datos = $scope.columnar.datos.concat(mtrxIngresos);
                $scope.columnar.datos.push(lineaTotalIngresos);
                $scope.columnar.datos.push(['EGRESOS'].concat(vacios));
                $scope.columnar.datos = $scope.columnar.datos.concat(mtrxEgresos);
                $scope.columnar.datos.push(lineaTotalEgresos);

                $scope.columnar.datos[0].push('TOTAL');
                $scope.columnar.datos[1].push('');
                for(let i = 2; i < $scope.columnar.datos.length; i++){
                    let fila = $scope.columnar.datos[i];
                    let suma = 0.0;
                    for(let j = 1; j < fila.length; j++){
                        if(typeof fila[j] === 'number'){
                            suma += fila[j];
                            $scope.columnar.datos[i][j] = $filter('number')($scope.columnar.datos[i][j], 2);
                        }

                    }
                    $scope.columnar.datos[i].push(suma !== 0.0 ? $filter('number')(suma, 2) : '');
                }

                //console.log($scope.columnar);
            };

            /*
            $scope.getXls = (idelemento) => {
                $(`#${idelemento}`).table2excel({
                    filename: `IngEgProyecto_${moment().format('YYYYMMDDHHmmss')}.xls`
                });
            }
            */

            $scope.ingegr = (conDetalle) => {
                $scope.params.detallado = +conDetalle;
                rptIngresosEgresosProySrvc.ingegr($scope.params).then(res => $scope.info = res);
            }
        }]);    
    }());