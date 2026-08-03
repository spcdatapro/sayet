(function () {

    const controller = angular.module('cpm.rptfactingreso', []);

    controller.controller('rptFactIngresoCtrl', ['$scope', 'jsReportSrvc', 'authSrvc', 'empresaSrvc', 'gerencialSrvc',
        function ($scope, jsReportSrvc, authSrvc, empresaSrvc, gerencialSrvc) {

            // variables para selectores
            $scope.empresas = [];

            // estatus de carga
            $scope.cargando = false;
            $scope.usuario = undefined;

            // parametros para reporte
            $scope.params = { anio: +moment().toDate().getFullYear(), idempresa: [] };

            // variable que guarda el reporte para mostrar en pantalla
            $scope.encabezado = {};
            $scope.anios = [];

            // para visualizaciones en pantalla
            $scope.ver = false;
            $scope.content = `${window.location.origin}/blank.html`

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
                $scope.params.idempresa = params.idempresa.push(usuario.workingon);
            })

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
            $scope.getPDF = (params) => {
                // estatus de carga
                $scope.cargando = true;
                // reinciar visualizacion
                resetVer();

                jsReportSrvc.getPDFReport('ryDqghxsZg', params).then(function (pdf) {
                    $scope.content = pdf;
                    $scope.cargando = false;
                })
            }

            // excel
            $scope.getXML = params => {
                // estatus de carga
                $scope.cargando = true;

                jsReportSrvc.getReport('SycMfI-hWl', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = params.anio;

                    saveAs(file, 'Reporte_Fact_Ingreso_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            }

            // reinicar visualizacion
            function resetVer() {
                $scope.ver = false;
                $scope.content = `${window.location.origin}/blank.html`
            }
        }])
}())