angular.module('cpm')
.controller('repPlanillaController', ['$scope', '$http', 'empresaSrvc', 'empServicios',
    function($scope, $http, empresaSrvc, empServicios){
        $scope.empresas = []
        $scope.empleados = []

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({sin_limite:1}).then(function(res){
            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        })
    }
])
.controller('repReciboController', ['$scope', '$http', 'empresaSrvc', 'empServicios',
    function($scope, $http, empresaSrvc, empServicios){
        $scope.empresas = []
        $scope.empleados = []

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({'sin_limite':1 }).then(function(res){
            res.resultados.forEach(value => {
                value.segundonombre = value.segundonombre ? value.segundonombre : '';
                value.tercernombre = value.tercernombre ? value.tercernombre : '';

                value.nombre = value.primernombre + ' ' + value.segundonombre + ' ' + value.tercernombre;

                value.segundoapellido = value.segundoapellido ? value.segundoapellido : '';
                value.apellidocasada = value.apellidocasada ? value.apellidocasada : '';

                value.apellidos = value.primerapellido + ' ' + value.segundoapellido + ' ' + value.apellidocasada;
            });

            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        });
    }
])
.controller('repFiniquitoController', ['$scope', '$http', 'empresaSrvc', 'empServicios',  '$confirm', '$window', '$uibModal', 'preServicios', 'toaster',
    function($scope, $http, empresaSrvc, empServicios, $confirm, $window, $uibModal, preServicios, toaster){
        $scope.empleados = [];
        $scope.params = { meses_calculo: 6, dias_sueldo_pagar: 0, otrosdesc_razon: null, otros_razon: null, vacas_del: null, vacas_al: null, fecha_egreso: moment().toDate(), guardar: false };
        $scope.cargando = false;

        empServicios.buscar({'sin_limite':1, 'estatus': 1}).then(function(res){
            res.resultados.forEach(value => {
                value.segundonombre = value.segundonombre ? value.segundonombre : '';
                value.tercernombre = value.tercernombre ? value.tercernombre : '';

                value.nombre = value.primernombre + ' ' + value.segundonombre + ' ' + value.tercernombre;

                value.segundoapellido = value.segundoapellido ? value.segundoapellido : '';
                value.apellidocasada = value.apellidocasada ? value.apellidocasada : '';

                value.apellidos = value.primerapellido + ' ' + value.segundoapellido + ' ' + value.apellidocasada;
            });

            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        });

        $scope.getFiniquito = () => {
            $uibModal.open({
                animation: true,
                templateUrl: 'modalFiniquito.html',
                controller: 'ModalFiniquito',
                windowClass: 'app-modal-window',
                resolve: {
                    params: $scope.params
                }
            }).result.then(function (obj) {
                obj.fechastr = formatoFechaStr(obj.fecha_egreso);
                $scope.cargando = true;

                // dar de baja y agregar movimiento en bitacora
                darBaja(obj);
            })
        };

        function agregarArchivo (pdf) {
            if ($scope.params.empleado) {
                let arc = {};
                arc.archivo = pdf;
                arc.fchvence = formatoFecha($scope.params.fecha_egreso);
                arc.idplnarchivotipo = '3';

                empServicios.agregarArchivo($scope.params.empleado, arc);
            }
        }

        function darBaja (datos) {
            // crear objeto para bitacora y baja de empleado
            empServicios.darBaja(datos)
            .then(d => {
                console.log(d);
                let bus = { empleado: datos.empleado, finalizado: 0 };
                preServicios.buscar(bus)
                .then(d => {
                    datos.idprestamos = d.resultados.map(p => p.id).join(',');
                    d.resultados.forEach(prestamo => {
                        let abono = {monto: prestamo.saldo, fecha: formatoFecha(datos.fecha_egreso), 
                            concepto: 'Liquidación del prestamo por baja de empleado.'};
                        // guardar abono
                        preServicios.guardarAbono(abono, prestamo.id);
                        // liquidar prestamo
                        let pres = { liquidacion: formatoFecha(datos.fecha_egreso), id: prestamo.id };
                        preServicios.guardar(pres);
                    })
                })
                .then(() => {
                    empServicios.getFiniquito(datos)
                    .then(function (pdf) { 
                        //mostrar el pdf en un ventana a parte
                        $window.open(pdf.pantalla);
                        // adjuntar el archivo al empleado
                        agregarArchivo(pdf.descarga);
                        $scope.cargando = false;      
                        // location.reload();
                    });
                })
            })
        }

        function formatoFecha (fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate();
        }

        function formatoFechaStr (fecha) {
            return fecha.getDate()+'-'+(fecha.getMonth()+1)+'-'+fecha.getFullYear();
        }
    }
])

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    .controller('ModalFiniquito', ['$scope', '$uibModalInstance', 'params', 'empServicios', '$window', function 
        ($scope, $uibModalInstance, params, empServicios, $window) {
        $scope.params = params;

        $scope.ok = function () { 
            params.guardar = true;
            $uibModalInstance.close(params); 
        };

        $scope.cancel = function () { 
            empServicios.getFiniquito(params).then((pdf) => { $window.open(pdf.pantalla); });
            $uibModalInstance.dismiss('cancel'); 
        };

    }])
.controller('repEmpleadoController', ['$scope', '$http', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, empresaSrvc, empServicios, proyectoSrvc){
        $scope.empresas = []
        $scope.proyectos = []
        $scope.empresasPlanilla

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresaDebito").chosen({width:'100%'}) }, 3)
        })

        proyectoSrvc.lstProyecto().then(function(d){
            $scope.proyectos = d;
            setTimeout(function() { $("#selectProyecto").chosen({width:'100%'}) }, 3)
        })

        empServicios.getEmpresas().then(function(res){
            $scope.empresasPlanilla = res.empresas
            setTimeout(function() { $("#selectEmpresaActual").chosen({width:'100%'}) }, 3)
        })
    }
])
.controller('repProyeccionController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, nominaServicios, empresaSrvc, empServicios, proyectoSrvc){
        $scope.empresas = []
        $scope.empleados = []
        $scope.fdel = ''
        $scope.fal = ''
        $scope.params = {
            empresa: '',
            empleado: '',
            fdel: '',
            fal: '',
            bono: '1'
        }
        $scope.lista = []
        $scope.total = ''

        empresaSrvc.lstEmpresas().then(function(d){
            $scope.empresas = d;
            setTimeout(function() { $("#selectEmpresa").chosen({width:'100%'}) }, 3)
        })

        empServicios.buscar({sin_limite:1}).then(function(res){
            console.log(res)

            $scope.empleados = res.resultados
            setTimeout(function() { $("#selectEmpleado").chosen({width:'100%'}) }, 3)
        })

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate()
        }

        $scope.generar = () => {
            $('#proyeccion-lista').DataTable().destroy()

            $scope.lista = []
            $scope.total = ''
            $scope.params.fdel = $scope.formatoFecha($scope.fdel)
            $scope.params.fal = $scope.formatoFecha($scope.fal)

            const btn = $('#btn-generar-proyeccion').button('loading')

            nominaServicios.generarProyeccion($scope.params)
            .then(res => {
                $scope.lista = res.lista
                $scope.total = res.total
                btn.button('reset')

                setTimeout(() => {
                    $('#proyeccion-lista').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excelHtml5','csv','pdf']
                    })
                })
            });
        }
    }
])
.controller('repMovimientoController', ['$scope', '$http', 'nominaServicios', 'empresaSrvc', 'empServicios', 'proyectoSrvc',
    function($scope, $http, nominaServicios, empresaSrvc, empServicios, proyectoSrvc){
        $scope.lista = []
        $scope.tipos = []
        $scope.tipo = null
        $scope.fdel = null
        $scope.fal = null

        empServicios.getCatalogo().then(function(data){
            $scope.tipos = data.movimiento
        });

        $scope.formatoFecha = function(fecha) {
            return fecha.getFullYear()+'-'+(fecha.getMonth()+1)+'-'+fecha.getDate()
        }

        $scope.generar = () => {
            $('#movimiento-lista').DataTable().destroy()

            $scope.lista = []

            const params = {
                movdel: $scope.formatoFecha($scope.fdel),
                moval: $scope.formatoFecha($scope.fal),
                movtipo: $scope.tipo
            }

            const btn = $('#btn-generar').button('loading')

            empServicios.getMovimiento(params)
            .then(res => {
                $scope.lista = res

                btn.button('reset')

                setTimeout(() => {
                    $('#movimiento-lista').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excelHtml5','csv','pdf']
                    })
                })
            });
        }
    }
]);