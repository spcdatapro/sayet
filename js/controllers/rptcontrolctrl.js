(function () {

    const controller = angular.module('cpm.rptcontrolctrl', []);

    controller.controller('hojasControlCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', 'proveedorSrvc', 'jsReportSrvc', 'tranBancSrvc', ($scope, authSrvc, empresaSrvc, proyectoSrvc, proveedorSrvc, jsReportSrvc, tranBancSrvc) => {

        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.proveedores = [];
        $scope.por_proveedor = true;
        $scope.cuentas_gastos = [];

        $scope.params_proveedores = { ver: '1', fecha_inicial: moment().startOf('year').toDate(), fecha_final: moment().endOf('month').toDate(),
            mes: (moment().month() + 1).toString(), mes_comparar: (moment().month() - 1 < 0 ? 12 : moment().month()).toString()
        };

        $scope.ver = false;
        $scope.usuario = undefined;
        $scope.cargando = false;
        $scope.content = `${window.location.origin}/blank.html`

        // servicio para traer la informacion del usuario
        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function (d) {
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => { idempresas.push(aut.id) });

                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                });
            });
            // globalizar usuario
            $scope.usuario = usuario.uid;
            // asignar empresa
            $scope.params_proveedores.idempresa = usuario.workingon.toString();
            // traer proyectos con la empresa del usuario
            $scope.getProyectos(usuario.workingon.toString());
            // traer cuentas de gasto para caratula de gastos
            $scope.getCuentasGastos(usuario.workingon.toString());
        })

        // traer proyectos al cambiar empresa
        $scope.getProyectos = idempresa => {
            proyectoSrvc.lstProyectosPorEmpresa(idempresa, $scope.usuario).then(d => $scope.proyectos = d);
            proveedorSrvc.lstProveedoresByEmpresa(idempresa, 0).then(d => {$scope.proveedores = d; });
            $scope.params_proveedores.idproyecto = undefined;
            $scope.params_proveedores.idproveedor = undefined;
        }

        // trae cuentas de gasto para caratula de gastos
        $scope.getCuentasGastos = idempresa => {
            proveedorSrvc.lstCuentasGastos(idempresa).then(d => $scope.cuentas_gastos = d);
        }

        // traer proveedores al cambiar proyecto/empresa
        $scope.getProveedores = (idempresa, idproyecto) => {
            if ($scope.params_proveedores.idproveedor > 0) {
                return;
            } else {
                proveedorSrvc.lstProveedoresByEmpresa(idempresa, idproyecto).then(d => { 
                    $scope.proveedores = d; 
                    $scope.actualizarProveedores($scope.params_proveedores.fecha_inicial);
                });
                $scope.params_proveedores.idproveedor = undefined;
            }
        }

        $scope.getProyectosProveedor = (idproveedor, idempresa) => {
            if (!$scope.params_proveedores.idproveedor) {
                $scope.getProyectos(idempresa);
                return;
            }
            console.log('llego');
            if ($scope.params_proveedores.idproyecto.length > 0) {
                return;
            } else {
                proyectoSrvc.listaProyectosProveedor(idproveedor, idempresa).then(d => $scope.proyectos = d);
                $scope.params_proveedores.idproyecto = undefined;
            }
        }

        // hoja de control proveedores

        $scope.getPdfProveedores = params => {
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');
            // control de errores en el reporteador
            jsReportSrvc.getPDFReport('HyoH1nfKxl', params)
                .then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getPdfProveedores:', err);
                })
        }

        // excel isr 
        $scope.getXmlProveedores = params => {
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');
            // control de errores en el reporteador
            jsReportSrvc.getReport('By1YuG8Fex', params)
                .then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.fecha_inicialstr + '_' + params.fecha_finalstr;
                    saveAs(file, 'Hoja_control_proveedores_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getXmlProveedores:', err);
                })
        }

        $scope.getReportProveedores = params => {
            // estatus carga
            $scope.cargando = true;
            $scope.content = `${window.location.origin}/blank.html`
            $scope.ver_comparativo = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');

            tranBancSrvc.datosReporteAprobados(params).then(d => {
                $scope.encabezado = d.encabezado;
                $scope.data = d.data;
                $scope.cargando = false;
                $scope.ver = true;
            })
        }

        $scope.getCaratula = params => { 
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');

            jsReportSrvc.getPDFReport('BJ_JkGH8-l', params)
                .then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getCaratula:', err);
                })
        }

        $scope.getCaratulaXML = params => { 
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');
            // control de errores en el reporteador

            jsReportSrvc.getReport('SJNU9iqdbx', params)
                .then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.fecha_inicialstr + '_' + params.fecha_finalstr;
                    saveAs(file, 'Caratula_proveedores' + rango + '.xlsx');

                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getCaratulaXML:', err);
                })
        }
        // fin hoja de control proveedores

        // comparativo {{por el momento solo proveedores, puede extenderse a clientes u otros}}
        $scope.getPdfComparativo = params => {
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;

            params.anio = moment(params.fecha_inicial).year();

            // control de errores en el reporteador
            jsReportSrvc.getPDFReport('H1_synquWl', params)
                .then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getPdfComparativo:', err);
                })
        }

        $scope.getReportComparativo = params => {
            // estatus carga
            $scope.cargando = true;
            // limpiar vista
            $scope.content = `${window.location.origin}/blank.html`
            $scope.ver = false;

            params.anio = moment(params.fecha_inicial).year();

            tranBancSrvc.datosReporteComparativo(params).then(d => {
                $scope.data = d.data;
                $scope.ver_comparativo = true;
                $scope.cargando = false;
                console.log(d);
            })
        }

        $scope.getXmlComparativo = params => {
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;

            params.anio = moment(params.fecha_inicial).year();

            // control de errores en el reporteador
            jsReportSrvc.getReport('HkKigvgcZg', params)
                .then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.mes + '_' + params.mes_comparar + '_' + params.anio;
                    saveAs(file, 'Comparativo_proveedores_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
                .catch(function (err) {
                    $scope.cargando = false;
                    console.error('Error en getXmlComparativo:', err);
                })
        }

        $scope.toggleMeses = d => {
            d.ver_meses = !d.ver_meses;
        };

        $scope.toggleAnios = d => {
            d.ver_anios = !d.ver_anios;
        }

        $scope.actualizarProveedores = (del) => {
            if (moment(del).isValid()) {
                $scope.proveedores.forEach(p => {
                    if (moment(p.last_fecha).format('YYYY-MM-DD') >= moment(del).format('YYYY-MM-DD')) {
                        p.historico = false;
                    } else {
                        p.historico = true;
                    }
                })
            }
            console.log($scope.proveedores);
        }
        // fin 
    }])
}())
