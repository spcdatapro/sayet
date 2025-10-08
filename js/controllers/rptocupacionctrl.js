(function () {

    const controller = angular.module('cpm.rptocupacion', []);

    controller.controller('rptOcupacion', ['$scope', 'jsReportSrvc', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', '$filter', 'gerencialSrvc',
        function ($scope, jsReportSrvc, authSrvc, empresaSrvc, proyectoSrvc, $filter, gerencialSrvc) {

            // variables para selectores
            $scope.empresas = [];
            $scope.proyectos = [];
            $scope.meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
                'Noviembre', 'Diciembre'];

            // estatus de carga
            $scope.cargando = false;
            $scope.usuario = undefined;

            // parametros para reporte
            $scope.params = { mes_del: '0', mes_al: '11', anio_del: +moment().toDate().getFullYear(), anio_al: +moment().toDate().getFullYear() };

            // variable que guarda el reporte para mostrar en pantalla
            $scope.encabezado = {};
            $scope.anios = [];

            // para visualizaciones en pantalla
            $scope.ver = false;
            $scope.content = `${window.location.origin}/sayet/blank.html`;

            // asignar la empresa en la que el usuario se encuentra
            authSrvc.getSession().then(usuario => {

                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(d => {
                    empresaSrvc.getEmpresaUsuario(usuario.uid).then(autorizado => {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        })

                        $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    })
                })

                // globalizar usuario
                $scope.usuario = usuario.uid;
                // asignar empresa
                $scope.params.idempresa = usuario.workingon.toString();
                // traer proyectos con la empresa del usuario
                $scope.getProyectos(usuario.workingon.toString());
            })

            // traer proyectos al cambiar empresa
            $scope.getProyectos = idempresa => {
                proyectoSrvc.lstProyectosPorEmpresa(idempresa, $scope.usuario).then(function (d) { $scope.proyectos = d; });
                $scope.params.idproyecto = undefined;
            }

            // reporte en pantalla
            $scope.getResumen = params => {
                // estatus de carga
                $scope.cargando = true;
                // reinciar cualquier visualizacion
                resetVer();

                gerencialSrvc.ocupacion(params).then(function (d) {
                    // globalizar variables
                    $scope.encabezado = d.encabezado;
                    $scope.anios = d.anios;

                    // activar visualizacion
                    $scope.ver = true;
                    $scope.cargando = false;
                })
            }

            // pdf
            $scope.getPDF = function (params) {
                // estatus de carga
                $scope.cargando = true;
                // reinciar visualizacion
                resetVer();

                jsReportSrvc.getPDFReport('Bkr3njFixg', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            }

            // excel
            $scope.getXML = function (params) {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('Bk9uyL23gg', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = $filter('getById')($scope.proyectos, params.idproyecto).nomproyecto;

                    saveAs(file, 'Reporte_Ocupacion_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            }

            // reinicar visualizacion
            function resetVer() {
                $scope.ver = false;
                $scope.content = `${window.location.origin}/sayet/blank.html`;
            }
        }])
}())