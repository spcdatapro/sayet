(function(){

    const rptcontinactctrl = angular.module('cpm.rptcontinactctrl', []);

    rptcontinactctrl.controller('rptContInactCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'clienteSrvc','jsReportSrvc', function($scope, empresaSrvc, proyectoSrvc, clienteSrvc, jsReportSrvc){

        $scope.params = { idempresa: undefined, idproyecto: undefined, idcliente: undefined, fdel: moment().startOf('year').toDate(), fal: moment().toDate(), usufructo: 0 };
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.empresas = [];  
        $scope.proyectos = [];  
        $scope.lstCliente = [];    

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });
        clienteSrvc.lstCliente().then(function(d){$scope.lstClientes = d;});

        $scope.loadProyectos = function() {
            proyectoSrvc.lstProyectosPorEmpresa($scope.params.idempresa).then(function(d){ $scope.proyectos = d; });
        };

        
        $scope.getLisContInavtivos = function(){
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : '';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto != undefined ? $scope.params.idproyecto : '';
            $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente != undefined ? $scope.params.idcliente : '';
            $scope.params.usufructo = !!$scope.params.usufructo ? $scope.params.usufructo : 0 ;
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport('Bk9A4R-cD', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {idempresa: undefined, idproyecto: undefined, idcliente: undefined, fdel: '' , fal: ''}; 
        };

        $scope.getLisContExcel = function(){
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : '';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto != undefined ? $scope.params.idproyecto : '';
            $scope.params.idcliente = $scope.params.idcliente !=null && $scope.params.idcliente != undefined ? $scope.params.idcliente : '';
            $scope.params.usufructo = !!$scope.params.usufructo ? $scope.params.usufructo : 0 ;
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            var test = false;
            jsReportSrvc.getReport(test ? '' : 'H1WrS0z5w', $scope.params).then(function (result) {
            var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
            var nombre =  moment($scope.params.fdelstr).format('DDMMYYYY') + '_' + moment($scope.params.falstr).format('DDMMYYYY');
            saveAs(file, 'LisConInactivos_' + nombre + '.xlsx');
        });
    };

    }]);

}());