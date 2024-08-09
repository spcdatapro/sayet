(function () {

    var rptalquileresctrl = angular.module('cpm.rptalquileresctrl', []);

    rptalquileresctrl.controller('rptAlquileresCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', 'authSrvc', 'tipoServicioVentaSrvc', 'clienteSrvc', function ($scope, empresaSrvc, proyectoSrvc, jsReportSrvc, authSrvc, tipoServicioVentaSrvc, clienteSrvc) {

        $scope.params = {
            fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), usuario: '', porlocal: 0, sinproy: 0, verinactivos: 0, solofacturados: 0, categoria: undefined
        };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.tipos = [];
        $scope.categorias = [];

        authSrvc.getSession().then(function (usrLogged) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function (d) {
                empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                });
            });
            $scope.params.usuario = getIniciales(usrLogged.nombre);
        });

        tipoServicioVentaSrvc.lstTSVenta().then(function (d) { $scope.tipos = d; });
        clienteSrvc.lstCatClie().then(function (d) { $scope.categorias = d; });

        prepParams = () => {
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.porlocal = !!$scope.params.porlocal ? $scope.params.porlocal : 0;
            $scope.params.verinactivos = !!$scope.params.verinactivos ? $scope.params.verinactivos : 0;
            $scope.params.solofacturados = !!$scope.params.solofacturados ? $scope.params.solofacturados : 0;
            $scope.params.empresa = $scope.aArreglo($scope.params.empresatmp, 'id');
            $scope.params.proyecto = $scope.aArreglo($scope.params.proyectotmp, 'id');
            $scope.params.tipo = $scope.aArreglo($scope.params.tipotmp, 'id');
            $scope.params.categoria = $scope.params.categoria != null && $scope.params.categoria != undefined ? $scope.params.categoria : '';
        };

        var test = false;
        $scope.getRptAlquileres = function () {
            prepParams();
            var qrep = test ? 'BysL28eNg' : 'BkeNRDgVe';
            if (+$scope.params.sinproy == 1) {
                qrep = 'HyZn7Y8RW';
            }

            jsReportSrvc.getPDFReport(qrep, $scope.params).then(function (pdf) { $scope.content = pdf; });
        };

        $scope.getRptAlquileresExcel = () => {
            prepParams();
            jsReportSrvc.getReport('rJt0jsU8L', $scope.params).then(function (result) {
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                saveAs(file, 'RepAlquileres.xlsx');
            });
        }

        $scope.resetParams = function () { $scope.params = { fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), porlocal: 0, sinproy: 0, categoria: undefined }; };

        $scope.mostrarProyectos = function () {
            $scope.proyectos = [];

            if ($scope.params.empresatmp) {
                $scope.params.empresatmp.forEach(function (e) {
                    proyectoSrvc.lstProyectosPorEmpresa(e.id).then(function (res) {
                        $scope.proyectos = $scope.proyectos.concat(res);
                    });
                });
            }
        }

        $scope.aArreglo = function (a, c) {
            if (a) {
                var tmp = [];

                a.forEach(function (d) { tmp.push(d[c]); });

                return tmp;
            } else {
                return [];
            }
        }

    }]);
}());


