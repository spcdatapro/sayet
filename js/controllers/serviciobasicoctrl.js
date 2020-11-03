(function () {

    var serviciobasicoctrl = angular.module('cpm.serviciobasicoctrl', []);

    serviciobasicoctrl.controller('servicioBasicoCtrl', ['$scope', 'servicioBasicoSrvc', 'tipoServicioVentaSrvc', 'empresaSrvc', '$filter', '$confirm', 'DTOptionsBuilder', 'proveedorSrvc', 'toaster', '$uibModal', 'authSrvc', function ($scope, servicioBasicoSrvc, tipoServicioVentaSrvc, empresaSrvc, $filter, $confirm, DTOptionsBuilder, proveedorSrvc, toaster, $uibModal, authSrvc) {

        $scope.tipos = [];
        $scope.empresas = [];
        $scope.proveedores = [];
        $scope.servicio = { pagacliente: 0, preciomcubsug: 0.00, mcubsug: 0.00, espropio: '1', notas: undefined, idpadre: undefined };
        $scope.servicios = [];
        $scope.historico = [];
        $scope.histocantbase = [];
        $scope.losProyectos = [];
        $scope.lasUnidades = [];
        $scope.meses = [
            {id: 1, mes: 'Enero'}, {id: 2, mes: 'Febrero'}, {id: 3, mes: 'Marzo'}, {id: 4, mes: 'Abril'}, {id: 5, mes: 'Mayo'}, {id: 6, mes: 'Junio'},
            {id: 7, mes: 'Julio'}, {id: 8, mes: 'Agosto'}, {id: 9, mes: 'Septiembre'}, {id: 10, mes: 'Octubre'}, {id: 11, mes: 'Noviembre'}, {id: 12, mes: 'Diciembre'}
        ];
        $scope.anio = [moment().year()]
        $scope.showForm = { servbas: false };
        $scope.usrdata = {};

        authSrvc.getSession().then(function (usrLogged) { $scope.usrdata = usrLogged; });

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

        tipoServicioVentaSrvc.lstTSVenta().then(function (d) { $scope.tipos = d; });
        empresaSrvc.lstEmpresas().then(function (d) { $scope.empresas = d; });
        proveedorSrvc.lstProveedores().then(function (d) { $scope.proveedores = d });

        function procDataServ(d) {
            for (var i = 0; i < d.length; i++) {
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

        $scope.getLstServicios = function () {
            servicioBasicoSrvc.lstServiciosBasicos(0).then(function (d) {
                $scope.servicios = procDataServ(d);
            });
        };

        $scope.getServicio = function (idservicio) {
            servicioBasicoSrvc.getServicioBasico(idservicio).then(function (d) {
                $scope.servicio = procDataServ(d)[0];
                $scope.servicio.objTipo = $filter('getById')($scope.tipos, $scope.servicio.idtiposervicio);
                $scope.servicio.objProveedor = $filter('getById')($scope.proveedores, $scope.servicio.idproveedor);
                $scope.servicio.objEmpresa = $filter('getById')($scope.empresas, $scope.servicio.idempresa);
                servicioBasicoSrvc.historico(idservicio).then(function (d) { $scope.historico = d; });
                servicioBasicoSrvc.historicoCantBase(idservicio).then(function (d) {
                    for (var i = 0; i < d.length; i++) { d[i].fechacambio = moment(d[i].fechacambio).toDate(); }
                    $scope.histocantbase = d;
                });
                $scope.showForm.servbas = true;
                goTop();
            });
        };

        $scope.resetservicio = function () { $scope.servicio = { pagacliente: 0, preciomcubsug: 0.00, mcubsug: 0.00, espropio: '1', debaja: 0, fechabaja: undefined, notas: undefined, idpadre: undefined }; };

        function setObjSend(obj) {
            obj.idtiposervicio = obj.objTipo.id;
            obj.idempresa = obj.objEmpresa.id;
            obj.ubicadoen = obj.ubicadoen != null && obj.ubicadoen != undefined ? obj.ubicadoen : '';
            obj.espropio = obj.espropio != null && obj.espropio != undefined ? obj.espropio : '0';
            obj.preciomcubsug = (obj.preciomcubsug != null && obj.preciomcubsug != undefined && +obj.espropio == 1) ? obj.preciomcubsug : 0.00;
            obj.mcubsug = 0.00;
            obj.pagacliente = +obj.espropio == 1 ? 0 : (obj.pagacliente != null && obj.pagacliente != undefined ? obj.pagacliente : 0);
            obj.idproveedor = +obj.espropio == 1 ? 0 : obj.objProveedor.id;
            obj.debaja = obj.debaja != null && obj.debaja != undefined ? obj.debaja : 0;
            obj.fechabajastr = obj.fechabaja != null && obj.fechabaja != undefined ? moment(obj.fechabaja).format('YYYY-MM-DD') : '';
            obj.cobrar = obj.cobrar != null && obj.cobrar != undefined ? obj.cobrar : 0;
            obj.notas = obj.notas != null && obj.notas != undefined ? obj.notas : '';
            obj.idpadre = obj.idpadre != null && obj.idpadre != undefined ? obj.idpadre : 0;

            return obj;
        }

        $scope.addServicio = function (obj) {
            //console.log(obj); return;
            obj = setObjSend(obj);
            servicioBasicoSrvc.editRow(obj, 'c').then(function (d) {
                $scope.getLstServicios();
                $scope.getServicio(parseInt(d.lastid));
            });
        };

        $scope.updServicio = function (obj) {
            //console.log(obj); return;
            obj = setObjSend(obj);
            servicioBasicoSrvc.editRow(obj, 'u').then(function (d) {
                $scope.getLstServicios();
                $scope.getServicio(parseInt(obj.id));
            });
        };

        $scope.delServicio = function (obj) {
            if (+obj.asignado == 0) {
                $confirm({
                    text: '¿Seguro(a) de eliminar este servicio?',
                    title: 'Eliminar servicio', ok: 'Sí', cancel: 'No'
                }).then(function () {
                    servicioBasicoSrvc.editRow({ id: obj.id }, 'd').then(function () {
                        $scope.getLstServicios();
                        $scope.resetservicio();
                    });
                });
            } else {
                toaster.pop('info', 'Servicios', 'Debe quitarlo de la unidad antes de poder eliminarlo...');
            }

        };

        $scope.getLstServicios();

        $scope.lecturaInicial = () => {
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalLecturaInicial.html',
                controller: 'ModalLecturaInicialCtrl',                
                resolve: {
                    idservicio: () => $scope.servicio.id,
                    idempresa: () => $scope.servicio.idempresa,
                    mes : () => $scope.meses, 
                    anio : () => $scope.anio,
                    usr: () => $scope.usrdata
                }
            });

            modalInstance.result.then(() => { }, () => { });
        };
    }]);

    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    serviciobasicoctrl.controller('ModalLecturaInicialCtrl', ['$scope', '$uibModalInstance', 'idservicio', 'idempresa', 'mes', 'anio', 'usr', 'proyectoSrvc', 'servicioBasicoSrvc', 'toaster', function ($scope, $uibModalInstance, idservicio, idempresa, mes, anio, usr, proyectoSrvc, servicioBasicoSrvc, toaster) {
        $scope.proyectos = [];
        $scope.unidades = [];
        $scope.mes = mes;
        $scope.anio = anio;
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        // console.log('PROYECTOS', $scope.proyectos);

        $scope.resetParams = () => $scope.params = { 
            idservicio: idservicio, idproyecto: undefined, idunidad: undefined,
            mes: (moment().month() + 1).toString(), anio: moment().year(), fechacorte: moment().toDate(),
            lectura: 0, idusuario: usr.uid
        };
        
        $scope.loadUnidades = (idproyecto, idunidad) => {
            proyectoSrvc.lstUnidadesProyecto(idproyecto).then((d) => {
                $scope.unidades = d;
                if (!!idunidad) {
                    $scope.params.idunidad = idunidad.toString();
                }
            });
        };

        servicioBasicoSrvc.getAsignacion(idservicio).then(res => {
            $scope.resetParams();
            // console.log('ASIGNA = ', res.asignacion);
            const asignacion = res.asignacion;
            proyectoSrvc.lstProyectosPorEmpresa(idempresa).then((d) => {                
                $scope.proyectos = d;
                if (asignacion.idproyecto > 0) {
                    $scope.params.idproyecto = asignacion.idproyecto.toString();
                    $scope.loadUnidades($scope.params.idproyecto, asignacion.idunidad);
                }
            });            
        });

        $scope.ok = () => {
            $scope.params.idproyecto = !!$scope.params.idproyecto ? $scope.params.idproyecto : '';
            $scope.params.idunidad = !!$scope.params.idunidad ? $scope.params.idunidad : '';
            $scope.params.mes;
            $scope.params.anio;
            $scope.params.fechacortestr = moment($scope.params.fechacorte).isValid() ? moment($scope.params.fechacorte).format('YYYY-MM-DD') : '';
            $scope.params.lectura = !!$scope.params.lectura ? $scope.params.lectura : 0;            
            servicioBasicoSrvc.editRow($scope.params, 'lecturainicial').then(() => {
                toaster.pop('success', 'Servicios', 'Se inserto la lectura incial del contador.');
                $uibModalInstance.close();
            });
        };

        $scope.cancel = () => $uibModalInstance.dismiss('cancel');
    }]);

}());