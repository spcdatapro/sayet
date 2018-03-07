(function(){

    var serviciopropioctrl = angular.module('cpm.serviciopropioctrl', []);

    serviciopropioctrl.controller('servicioPropioCtrl', ['$scope', 'servicioPropioSrvc', 'tipoServicioVentaSrvc', 'empresaSrvc', '$filter', '$confirm', 'DTOptionsBuilder', 'proveedorSrvc', function($scope, servicioPropioSrvc, tipoServicioVentaSrvc, empresaSrvc, $filter, $confirm, DTOptionsBuilder, proveedorSrvc){

        $scope.tipos = [];
        $scope.empresas = [];
        $scope.proveedores = [];
        $scope.servicio = {preciomcubsug: 0.00, mcubsug: 0.00};
        $scope.servicios = [];
        $scope.historico = [];
        $scope.showForm = { servprop: false };

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap()
            .withBootstrapOptions({
                pagination: {
                    classes: {
                        ul: 'pagination pagination-sm'
                    }
                }
            })
            .withOption('responsive', true);

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tipos = d; });
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        function procDataServ(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idtiposervicio = parseInt(d[i].idtiposervicio);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].preciomcubsug = parseFloat(d[i].preciomcubsug);
                d[i].mcubsug = parseFloat(d[i].mcubsug);
            }
            return d;
        }

        $scope.getLstServicios = function(){
            servicioPropioSrvc.lstServicios(0).then(function(d){
                $scope.servicios = procDataServ(d);
            });
        };

        $scope.getServicio = function(idservicio){
            servicioPropioSrvc.getServicio(idservicio).then(function(d){
                $scope.servicio = procDataServ(d)[0];
                $scope.servicio.objTipo = $filter('getById')($scope.tipos, $scope.servicio.idtiposervicio);
                $scope.servicio.objEmpresa = $filter('getById')($scope.empresas, $scope.servicio.idempresa);
                servicioPropioSrvc.historico(idservicio).then(function(d){ $scope.historico = d; });
                $scope.showForm.servprop = true;
                goTop();
            });
        };

        $scope.resetservicio = function(){ $scope.servicio = {preciomcubsug: 0.00, mcubsug: 0.00}; };

        function prepServ(obj){
            obj.idtiposervicio = obj.objTipo.id;
            obj.idempresa = obj.objEmpresa.id;
            obj.preciomcubsug = obj.preciomcubsug != null && obj.preciomcubsug != undefined ? obj.preciomcubsug : 0.00;
            obj.mcubsug = obj.mcubsug != null && obj.mcubsug != undefined ? obj.mcubsug : 0.00;
            return obj;
        }

        $scope.addServicio = function(obj){
            obj = prepServ(obj);
            servicioPropioSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstServicios();
                $scope.getServicio(parseInt(d.lastid));
            });
        };

        $scope.updServicio = function(obj){
            obj = prepServ(obj);
            servicioPropioSrvc.editRow(obj, 'u').then(function(d){
                $scope.getLstServicios();
                $scope.getServicio(parseInt(obj.id));
            });
        };

        $scope.delServicio = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar este servicio?',
                title: 'Eliminar servicio', ok: 'Sí', cancel: 'No'}).then(function() {
                servicioPropioSrvc.editRow({id: obj.id}, 'd').then(function(){
                    $scope.getLstServicios();
                    $scope.resetservicio();
                });
            });
        };

        $scope.getLstServicios();

    }]);

}());
