(function(){

    var serviciobasicoctrl = angular.module('cpm.serviciobasicoctrl', []);

    serviciobasicoctrl.controller('servicioBasicoCtrl', ['$scope', 'servicioBasicoSrvc', 'tipoServicioVentaSrvc', 'empresaSrvc', '$filter', '$confirm', 'DTOptionsBuilder', 'proveedorSrvc', 'toaster', function($scope, servicioBasicoSrvc, tipoServicioVentaSrvc, empresaSrvc, $filter, $confirm, DTOptionsBuilder, proveedorSrvc, toaster){

        $scope.tipos = [];
        $scope.empresas = [];
        $scope.proveedores = [];
        $scope.servicio = {pagacliente:0, preciomcubsug: 0.00, mcubsug: 0.00, espropio: '1', notas: undefined, idpadre: undefined};
        $scope.servicios = [];
        $scope.historico = [];
        $scope.histocantbase = [];
        $scope.showForm = { servbas: false };

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap()
            .withBootstrapOptions({
                pagination: {
                    classes: {
                        ul: 'pagination pagination-sm'
                    }
                }
            })
            .withOption('responsive', true)
            .withOption('paging', false);

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tipos = d; });
        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        proveedorSrvc.lstProveedores().then(function(d){ $scope.proveedores = d});

        function procDataServ(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idpadre = parseInt(d[i].idpadre);
                d[i].idtiposervicio = parseInt(d[i].idtiposervicio);
                d[i].idproveedor = parseInt(d[i].idproveedor);
                d[i].idempresa = parseInt(d[i].idempresa);
                d[i].pagacliente = parseInt(d[i].pagacliente);
                d[i].preciomcubsug = parseFloat(d[i].preciomcubsug);
                d[i].mcubsug = parseFloat(d[i].mcubsug);
                d[i].debaja = parseInt(d[i].debaja);
                d[i].fechabaja = moment(d[i].fechabaja).isValid() ? moment(d[i].fechabaja).toDate() : undefined;
                d[i].nivel = parseInt(d[i].nivel);
                d[i].cobrar = parseInt(d[i].cobrar);
            }
            return d;
        }

        $scope.getLstServicios = function(){
            servicioBasicoSrvc.lstServiciosBasicos(0).then(function(d){
                $scope.servicios = procDataServ(d);
            });
        };

        $scope.getServicio = function(idservicio){
            servicioBasicoSrvc.getServicioBasico(idservicio).then(function(d){
                $scope.servicio = procDataServ(d)[0];
                $scope.servicio.objTipo = $filter('getById')($scope.tipos, $scope.servicio.idtiposervicio);
                $scope.servicio.objProveedor = $filter('getById')($scope.proveedores, $scope.servicio.idproveedor);
                $scope.servicio.objEmpresa = $filter('getById')($scope.empresas, $scope.servicio.idempresa);
                servicioBasicoSrvc.historico(idservicio).then(function(d){ $scope.historico = d; });
                servicioBasicoSrvc.historicoCantBase(idservicio).then(function(d){
                    for(var i = 0; i < d.length; i++){ d[i].fechacambio = moment(d[i].fechacambio).toDate(); }
                    $scope.histocantbase = d;
                });
                $scope.showForm.servbas = true;
                goTop();
            });
        };

        $scope.resetservicio = function(){ $scope.servicio = {pagacliente:0, preciomcubsug: 0.00, mcubsug: 0.00, espropio: '1', debaja: 0, fechabaja: undefined, notas: undefined, idpadre: undefined }; };

        function setObjSend(obj){
            obj.idtiposervicio = obj.objTipo.id;
            obj.idempresa = obj.objEmpresa.id;
            obj.ubicadoen = obj.ubicadoen != null && obj.ubicadoen != undefined ? obj.ubicadoen : '';
            obj.espropio = obj.espropio != null && obj.espropio != undefined ? obj.espropio : '0';
            obj.preciomcubsug = (obj.preciomcubsug != null && obj.preciomcubsug != undefined && +obj.espropio == 1) ? obj.preciomcubsug : 0.00;
            obj.mcubsug = 0.00;
            obj.pagacliente =  +obj.espropio == 1 ? 0 : (obj.pagacliente != null && obj.pagacliente != undefined ? obj.pagacliente : 0);
            obj.idproveedor = +obj.espropio == 1 ? 0 : obj.objProveedor.id;
            obj.debaja = obj.debaja != null && obj.debaja != undefined ? obj.debaja : 0;
            obj.fechabajastr = obj.fechabaja != null && obj.fechabaja != undefined ? moment(obj.fechabaja).format('YYYY-MM-DD') : '';
            obj.cobrar = obj.cobrar != null && obj.cobrar != undefined ? obj.cobrar : 0;
            obj.notas = obj.notas != null && obj.notas != undefined ? obj.notas : '';
            obj.idpadre = obj.idpadre != null && obj.idpadre != undefined ? obj.idpadre : 0;

            return obj;
        }

        $scope.addServicio = function(obj){
            //console.log(obj); return;
            obj = setObjSend(obj);
            servicioBasicoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstServicios();
                $scope.getServicio(parseInt(d.lastid));
            });
        };

        $scope.updServicio = function(obj){
            //console.log(obj); return;
            obj = setObjSend(obj);
            servicioBasicoSrvc.editRow(obj, 'u').then(function(d){
                $scope.getLstServicios();
                $scope.getServicio(parseInt(obj.id));
            });
        };

        $scope.delServicio = function(obj){
            if(+obj.asignado == 0){
                $confirm({text: '¿Seguro(a) de eliminar este servicio?',
                    title: 'Eliminar servicio', ok: 'Sí', cancel: 'No'}).then(function() {
                    servicioBasicoSrvc.editRow({id: obj.id}, 'd').then(function(){
                        $scope.getLstServicios();
                        $scope.resetservicio();
                    });
                });
            }else{
                toaster.pop('info', 'Servicios', 'Debe quitarlo de la unidad antes de poder eliminarlo...');
            }
            
        };

        $scope.getLstServicios();

    }]);

}());