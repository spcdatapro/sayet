(function () {

    const controller = angular.module('cpm.rptcontrolctrl', []);

    controller.controller('hojasControlCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'proyectoSrvc', 'proveedorSrvc', 'jsReportSrvc', ($scope, authSrvc, empresaSrvc, proyectoSrvc, proveedorSrvc, jsReportSrvc) => {

        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.proveedores = [];
        // $scope.cuentas_prov = [];

        $scope.params_proveedores = { ver: '1', anio_del: +moment().toDate().getFullYear(), anio_al: +moment().toDate().getFullYear() };

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
            proyectoSrvc.lstProyectosPorEmpresa(idempresa, $scope.usuario).then(function (d) { $scope.proyectos = d });
            $scope.params_proveedores.idproyecto = undefined;
            $scope.params_proveedores.idproveedor = undefined;
        }

        // traer proveedores al cambiar proyecto/empresa
        $scope.getProveedores = (idempresa, idproyecto) => {
            proveedorSrvc.lstProveedoresByEmpresa(idempresa, idproyecto).then(d => { $scope.proveedores = d });
            $scope.params_proveedores.idproveedor = undefined;
        }

        // $scope.getCuentaContable = (idproveedor, idempresa) => {
        //     proveedorSrvc.getLstCuentasCont(idproveedor, idempresa).then(d => $scope.cuentas_prov = d);
        // }

        // hoja de control proveedores

        // para asegurar 
        $scope.$watch('params_proveedores.ver', newVal => {
            switch (newVal) {
                case '1':
                    $scope.params_proveedores.anio_del = +moment().toDate().getFullYear();
                    $scope.params_proveedores.anio_al = +moment().toDate().getFullYear();
                    break;
                case '2': 
                    $scope.params_proveedores.anio_del = +moment().toDate().getFullYear();
                    $scope.params_proveedores.anio_al = +moment().toDate().getFullYear();
                    break;
                case '3':
                    $scope.params_proveedores.anio_del = undefined;
                    $scope.params_proveedores.anio_al = undefined;
                    break;
            }
        })


        $scope.getPdfProveedores = params => {
            // estatus de carga
            $scope.cargando = true;
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
            // control de errores en el reporteador
            try {
                jsReportSrvc.getReport('By1YuG8Fex', params).then(function (result) {
                    var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                    let rango = undefined;

                    switch (params.ver) {
                        case '1':
                            rango = params.anio;
                        break;
                        case '2': 
                            rango = params.anio_del + '_' + params.anio_al;
                        break;
                        case '3':
                            rango = 'todos';
                    }

                    saveAs(file, 'Hoja_control_proveedores_' + rango + '.xlsx');

                    $scope.cargando = false;
                })
            } catch (err) {
                $scope.cargando = false;
                console.log(err);
            }
        }
        // fin hoja de control proveedores
    }])
}())
