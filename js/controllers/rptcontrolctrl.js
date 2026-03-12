(function () {

    const controller = angular.module('cpm.rptcontrolctrl', []);

    controller.controller('hojasControlCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', 'proveedorSrvc', 'jsReportSrvc', 'tranBancSrvc', ($scope, authSrvc, empresaSrvc, proyectoSrvc, proveedorSrvc, jsReportSrvc, tranBancSrvc) => {

        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.proveedores = [];
        // $scope.cuentas_prov = [];

        $scope.params_proveedores = { ver: '1', fecha_inicial: moment().startOf('year').toDate(), fecha_final: moment().endOf('month').toDate(),
            mes: (moment().month() + 1).toString(), mes_comparar: (moment().month() - 1 < 0 ? 12 : moment().month()).toString()
        };

        $scope.ver = false;
        $scope.usuario = undefined;
        $scope.cargando = false;
        $scope.content = `${window.location.origin}/sayet/blank.html`;

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
        })

        // traer proyectos al cambiar empresa
        $scope.getProyectos = idempresa => {
            proyectoSrvc.lstProyectosPorEmpresa(idempresa, $scope.usuario).then(d => $scope.proyectos = d);
            proveedorSrvc.lstProveedoresByEmpresa(idempresa, 0).then(d => {$scope.proveedores = d; });
            $scope.params_proveedores.idproyecto = undefined;
            $scope.params_proveedores.idproveedor = undefined;
        }

        // traer proveedores al cambiar proyecto/empresa
        $scope.getProveedores = (idempresa, idproyecto) => {
            if ($scope.params_proveedores.idproveedor > 0) {
                return;
            } else {
                proveedorSrvc.lstProveedoresByEmpresa(idempresa, idproyecto).then(d => { $scope.proveedores = d });
                $scope.params_proveedores.idproveedor = undefined;
            }
        }

        $scope.getProyectosProveedor = (idproveedor, idempresa) => {
            if (!$scope.params_proveedores.idproveedor) {
                $scope.getProyectos(idempresa);
                return;
            }
            console.log('llego');
            if ($scope.params_proveedores.idproyecto > 0) {
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
            try {
                jsReportSrvc.getPDFReport('HyoH1nfKxl', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
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
            try {
                jsReportSrvc.getReport('By1YuG8Fex', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.fecha_inicialstr + '_' + params.fecha_finalstr;
                    saveAs(file, 'Hoja_control_proveedores_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
        }

        $scope.getReportProveedores = params => {
            // estatus carga
            $scope.cargando = true;
            $scope.content = `${window.location.origin}/sayet/blank.html`;
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

            try {
                jsReportSrvc.getPDFReport('BJ_JkGH8-l', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
        }

        $scope.getCaratulaXML = params => { 
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            params.fecha_inicialstr = moment(params.fecha_inicial).format('YYYY-MM-DD');
            params.fecha_finalstr = moment(params.fecha_final).format('YYYY-MM-DD');
            // control de errores en el reporteador

            // control de errores en el reporteador
            try {
                jsReportSrvc.getReport('SJNU9iqdbx', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.fecha_inicialstr + '_' + params.fecha_finalstr;
                    saveAs(file, 'Caratula_proveedores' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
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
            try {
                jsReportSrvc.getPDFReport('H1_synquWl', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
        }

        $scope.getReportComparativo = params => {
            // estatus carga
            $scope.cargando = true;
            // limpiar vista
            $scope.content = `${window.location.origin}/sayet/blank.html`;
            $scope.ver = false;

            params.anio = moment(params.fecha_inicial).year();

            tranBancSrvc.datosReporteComparativo(params).then(d => {
                $scope.data = d.data;
                $scope.ver_comparativo = true;
                $scope.cargando = false;
                console.log(d);
            })
        }

        $scope.getXmlProveedores = params => {
            // estatus de carga
            $scope.cargando = true;
            $scope.ver = false;
            $scope.ver_comparativo = false;

            params.anio = moment(params.fecha_inicial).year();

            // control de errores en el reporteador
            try {
                jsReportSrvc.getReport('HkKigvgcZg', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;
                    rango = params.mes + '_' + params.mes_comparar + '_' + params.anio;
                    saveAs(file, 'Comparativo_proveedores_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
        }

        $scope.toggleMeses = function (d) {
            d.ver_meses = !d.ver_meses;

            // si se cierra, también resetea los detalles de cada año
            // if (!d.ver_anios && d.proyectos) {
            //     d.proyectos.forEach(function (anio) {
            //         anio.ver_detalle = false;
            //     });
            // }
        };
        // fin 
    }])
}())
