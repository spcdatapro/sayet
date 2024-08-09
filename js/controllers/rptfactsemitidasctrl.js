(function () {

    var rptfactsemitidasctrl = angular.module('cpm.rptfactsemitidasctrl', []);

    rptfactsemitidasctrl.controller('rptFacturasEmitidasCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', 'tipoServicioVentaSrvc', 'tipoCambioSrvc', 'factEmitidaSrvc', '$confirm',
        'toaster', 'authSrvc', '$route', function ($scope, empresaSrvc, jsReportSrvc, proyectoSrvc, tipoServicioVentaSrvc, tipoCambioSrvc, factEmitidaSrvc, $confirm, toaster, authSrvc, $route) {

            $scope.params = {
                idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), cliente: '', tipo: '1', idcliente: 0, idproyecto: undefined,
                idtsventa: undefined, soloanuladas: 0, tc: undefined
            };
            $scope.empresas = [];
            $scope.content = `${window.location.origin}/sayet/blank.html`;
            $scope.proyectos = [];
            $scope.tsventa = [];
            $scope.cargando = false;
            $scope.pendientes = {};
            $scope.generales = {};
            $scope.permisos = {};

            authSrvc.getSession().then(function (usuario) {
                authSrvc.gpr({ idusuario: parseInt(usuario.uid), ruta: $route.current.params.name }).then(function (p) {
                    $scope.permisos = p;
                });
                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });
                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    });
                });
            });

            tipoServicioVentaSrvc.lstTSVenta().then((d) => $scope.tsventa = d);

            tipoCambioSrvc.getTC().then(() => tipoCambioSrvc.getLastTC().then((d) => $scope.params.tc = parseFloat(parseFloat(d.lasttc).toFixed(2))));

            $scope.loadProyectos = (idempresa) => proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then((d) => $scope.proyectos = d);

            $scope.clienteSelected = (item) => {
                if (item != null && item != undefined) {
                    switch (typeof item.originalObject) {
                        case 'string':
                            $scope.params.cliente = item.originalObject;
                            $scope.params.idcliente = 0;
                            break;
                        case 'object':
                            $scope.params.cliente = item.originalObject.facturara;
                            $scope.params.idcliente = item.originalObject.idcliente;
                            break;
                    }
                }
            };

            $scope.focusOut = (item) => {
                if (item == null || item === undefined || item.toString().trim() === '') {
                    $scope.params.cliente = '';
                    $scope.params.idcliente = 0;
                }
            };

            let test = false;
            $scope.getFactsEmitidas = () => {
                $scope.cargando = true;
                $scope.ver = false;
                let reporte = 'BJW6LWoYb';
                $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
                $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
                $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : '';
                $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente !== undefined ? $scope.params.cliente : '';
                $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente !== undefined ? $scope.params.idcliente : 0;
                $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo !== undefined ? $scope.params.tipo : '1';
                $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
                $scope.params.idtsventa = $scope.params.idtsventa != null && $scope.params.idtsventa !== undefined ? $scope.params.idtsventa : 0;
                $scope.params.soloanuladas = $scope.params.soloanuladas != null && $scope.params.soloanuladas !== undefined ? $scope.params.soloanuladas : 0;

                switch (+$scope.params.tipo) {
                    case 4: reporte = 'ByqyuVFnW'; break;
                    case 5: reporte = 'B1nLiotFw'; break;
                    case 6: reporte = 'HkRpuReRP'; break;
                    default: reporte = 'BJW6LWoYb';
                }

                //console.log($scope.params); return;
                jsReportSrvc.getPDFReport(test ? '' : reporte, $scope.params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                });
            };

            $scope.getPendientes = function (params) {
                $scope.cargando = true;
                $scope.content = `${window.location.origin}/sayet/blank.html`;
                params.fdelstr = moment(params.fdel).format('YYYY-MM-DD');
                params.falstr = moment(params.fal).format('YYYY-MM-DD');
                $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : '';
                $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente !== undefined ? $scope.params.cliente : '';
                $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente !== undefined ? $scope.params.idcliente : 0;
                $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo !== undefined ? $scope.params.tipo : '1';
                $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
                $scope.params.idtsventa = $scope.params.idtsventa != null && $scope.params.idtsventa !== undefined ? $scope.params.idtsventa : 0;
                $scope.params.soloanuladas = $scope.params.soloanuladas != null && $scope.params.soloanuladas !== undefined ? $scope.params.soloanuladas : 0;

                factEmitidaSrvc.pendientes(params).then(function (d) {
                    $scope.generales = d.generales;
                    $scope.empresas = d.pendientes;
                    $scope.cargando = false;
                    $scope.ver = true;
                });
            }

            $scope.eliminarPendiente = function (idcargo, tipo) {
                let idtipo = tipo == 'Agua' ? 2 : 1;
                let fact = { id: idcargo, tipo: idtipo };

                $confirm({
                    text: '¿Seguro desea eliminar este cargo?',
                    title: 'Eliminar cargo pendiente', ok: 'Sí', cancel: 'No'
                }).then(function () {
                    factEmitidaSrvc.eliminarCargo(fact).then(function (d) {
                        toaster.pop({
                            type: d.tipo, title: 'Remover factura pendiente',
                            body: d.mensaje, timeout: 7000
                        });
                        $scope.getPendientes($scope.params);
                    });
                });
            }

        }]);

}());