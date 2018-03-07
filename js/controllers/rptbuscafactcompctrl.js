(function(){
    
        var rptbuscafactcompctrl = angular.module('cpm.rptbuscafactcompctrl', []);
    
        rptbuscafactcompctrl.controller('rptBuscaFacturaCompraCtrl', ['$scope', 'compraSrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', 'toaster', function($scope, compraSrvc, authSrvc, empresaSrvc, proyectoSrvc, toaster){
    
            //$scope.lasEmpresas = undefined;
            $scope.empresas = []; 
            $scope.params = { 
                qfecha:"fechafactura", fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), orderby: "b.nomempresa", 
                proveedor: undefined, nit: undefined, idempresa: undefined, concepto: undefined, serie: undefined, documento: undefined, lasEmpresas: undefined
            };
            $scope.compras = [];   
            $scope.proyectos = []; 

            empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
            proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });

            function chkVal(valor, retorno){return valor != null && valor != undefined ? valor : retorno; }            
                        
            $scope.getFacturas = function(){
                $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
                $scope.params.proveedor = chkVal($scope.params.proveedor, '');
                $scope.params.nit = chkVal($scope.params.nit, '');                              
                $scope.params.concepto = chkVal($scope.params.concepto, '');
                $scope.params.serie = chkVal($scope.params.serie, '');
                $scope.params.documento = chkVal($scope.params.proveedor, 0);

                //console.log($scope.params.lasEmpresas);

                if($scope.params.lasEmpresas){
                    if($scope.params.lasEmpresas.length > 0){
                        $scope.params.idempresa = objectPropsToList($scope.params.lasEmpresas, 'id', ',');
                    }else{ $scope.params.idempresa = ''; }                    
                }else{ $scope.params.idempresa = ''; }

                //console.log($scope.params);

                compraSrvc.buscaFactura($scope.params).then(function(d){
                    $scope.compras = d;
                });

            };

            $scope.updCompraProy = function(idcompra, idproyecto){
                compraSrvc.editRow({idcompra: +idcompra, idproyecto: +idproyecto}, 'updproycomp').then(function(){
                    toaster.pop({ type: 'success', title: 'Compra actualizada', body: 'Se asigno proyecto a la factura', timeout: 5000 }); 
                });
            };

            $scope.resetParams = function(){
                $scope.params = { 
                    qfecha:"fechafactura", fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), orderby: "b.nomempresa", 
                    proveedor: undefined, nit: undefined, idempresa: undefined, concepto: undefined, serie: undefined, documento: undefined
                };                
            };    
        }]);
    
    }());
    