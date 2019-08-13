(function(){

    const rptanticlictrl = angular.module('cpm.rptanticlictrl', []);

    rptanticlictrl.controller('rptAntiClientesCtrl', ['$scope',  'authSrvc', 'jsReportSrvc', '$sce','clienteSrvc','empresaSrvc', 'proyectoSrvc', ($scope, authSrvc, jsReportSrvc, $sce, clienteSrvc, empresaSrvc, proyectoSrvc) => {

        $scope.params = {
            al: moment().toDate(), idempresa: undefined, idproyecto: undefined, detallada: 1, orderalfa: 1, cliente: undefined
        };
        $scope.content = undefined;
        //$scope.clientes = [];
        $scope.empresas = [];
        $scope.proyectos = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        //clienteSrvc.lstCliente().then((d) => $scope.clientes = d);

        $scope.loadProyectos = (idempresa) => proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then((d) => $scope.proyectos = d);

        $scope.resetParams = () => {
            $scope.params = {
                al: moment().toDate(), idempresa: undefined, idproyecto: undefined, detallada: 1, orderalfa: 1, cliente: undefined
            };
            $scope.$broadcast('angucomplete-alt:clearInput', 'txtCliente');
        };

        $scope.clienteSelected = (item) => {
            if(item != null && item != undefined){
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

        setParams = () => {
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : 0;
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
            $scope.params.detallada = $scope.params.detallada != null && $scope.params.detallada !== undefined ? +$scope.params.detallada : 0;
            $scope.params.orderalfa = $scope.params.orderalfa != null && $scope.params.orderalfa !== undefined ? +$scope.params.orderalfa : 0;
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente !== undefined ? $scope.params.cliente : '';
        };

        const test = false;
        $scope.getAntiCli = () => {
            setParams();
            jsReportSrvc.getPDFReport(test ? 'rJfbwLe4B' : 'rJfbwLe4B', $scope.params).then((pdf) => $scope.content = pdf);
        };

        /*
        $scope.getAntiCliXLSX = function(){
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.clistr = $scope.params.cliente.id;

            if($scope.params.detalle == 1){
                jsReportSrvc.antiClientesDetXlsx($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    saveAs(file, 'AntiClientes.xlsx');
                });
            }else {
                jsReportSrvc.antiClientesXlsx($scope.params).then(function (result) {
                    var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    saveAs(file, 'AntiClientes.xlsx');
                });
            }
        };
        */
    }]);

}());

