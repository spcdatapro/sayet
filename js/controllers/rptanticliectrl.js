(function(){

    const rptanticlictrl = angular.module('cpm.rptanticlictrl', []);

    rptanticlictrl.controller('rptAntiClientesCtrl', ['$scope',  'authSrvc', 'jsReportSrvc', '$sce','clienteSrvc','empresaSrvc', 'proyectoSrvc', ($scope, authSrvc, jsReportSrvc, $sce, clienteSrvc, empresaSrvc, proyectoSrvc) => {

        $scope.params = { al: moment().toDate(), detallada: 1, orderalfa: 1, pagoextra: 0, vernegativos: 1, abreviado: 0, 
            idempresa: [] };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];
        $scope.proyectos = [];
        $scope.cargando = false;

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        //clienteSrvc.lstCliente().then((d) => $scope.clientes = d);

        $scope.loadProyectos = function (idempresa) { 
            if ($scope.params.idempresa.length > 1) {
                $scope.proyectos = [];
                $scope.params.idproyecto = undefined;
            } else {
                proyectoSrvc.lstProyectosPorEmpresa(+idempresa[0]).then((d) => $scope.proyectos = d);
            }
        }

        $scope.resetParams = () => {
            $scope.params = {
                al: moment().toDate(), idempresa: [], idproyecto: undefined, detallada: 1, orderalfa: 1, cliente: undefined, 
                pagoextra: 0, vernegativos: 1, abreviado: 0 };
            $scope.$broadcast('angucomplete-alt:clearInput', 'txtCliente');
        };

        $scope.clienteSelected = (item) => {
            if(item != null && item != undefined) {
                switch(typeof item.originalObject){
                    case 'string':
                        $scope.params.cliente = item.originalObject;
                        break;
                    case 'object':
                        $scope.params.cliente = item.originalObject.nombre;
                        break;
                }
            }
        };

        $scope.getAntiCli = (params) => {
            $scope.cargando = true;
            params.falstr = moment(params.al).format('YYYY-MM-DD');
            let rpt = params.abreviado ? 'B1lebL0Dv' : params.pagoextra ? 'B1UScXIcS' : 'SkRirvMBW';
            jsReportSrvc.getPDFReport(rpt, params).then(function (pdf) { 
                $scope.content = pdf;
                $scope.cargando = false;
            });
        };

        $scope.getAntiCliXLSX = (params) => {
            $scope.cargando = true;
            params.falstr = moment(params.al).format('YYYY-MM-DD');
            jsReportSrvc.getReport('ryQhx5gNr', params).then((result) => {
                const file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                const nombre = `ASC_${moment($scope.params.al).format('DDMMYYYY')}_${moment().format('DDMMYYYYHHmmss')}`;
                saveAs(file, `${nombre}.xlsx`);
                $scope.cargando = false;
            });
        };

    }]);

}());

