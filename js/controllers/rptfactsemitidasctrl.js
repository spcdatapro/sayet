(function(){

    var rptfactsemitidasctrl = angular.module('cpm.rptfactsemitidasctrl', []);

    rptfactsemitidasctrl.controller('rptFacturasEmitidasCtrl', ['$scope', 'authSrvc', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', 'tipoServicioVentaSrvc', function($scope, authSrvc, empresaSrvc, jsReportSrvc, proyectoSrvc, tipoServicioVentaSrvc){

        $scope.params = { idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), cliente: '', tipo: '1', idcliente: 0, idproyecto: undefined, idtsventa: undefined };
        $scope.empresas = [];
        $scope.content = '';
        $scope.proyectos = [];
        $scope.tsventa = [];

        empresaSrvc.lstEmpresas().then(function(d){ $scope.empresas = d; });

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){ $scope.tsventa = d; });

        /*
        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(d){
                    $scope.params.idempresa = d[0].id;
                    $scope.loadProyectos($scope.params.idempresa);
                });
            }
        });
        */

        $scope.loadProyectos = function(idempresa){
            proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then(function(d){ $scope.proyectos = d; });
        };

        $scope.clienteSelected = function(item){
            if(item != null && item != undefined){
                switch(typeof item.originalObject){
                    case 'string':
                        $scope.params.cliente = item.originalObject;
                        $scope.params.idcliente = 0;
                        break;
                    case 'object':
                        $scope.params.cliente = item.originalObject.facturara;
                        $scope.params.idcliente = item.originalObject.idcliente;
                        break;
                }
            }
        };

        $scope.focusOut = function(item){
            if(item == null || item === undefined || item.toString().trim() === ''){
                $scope.params.cliente = '';
                $scope.params.idcliente = 0;
            }
        };

        var test = false;
        $scope.getFactsEmitidas = function(){
            var reporte = 'BJW6LWoYb';
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : '';
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente !== undefined ? $scope.params.cliente : '';
            $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente !== undefined ? $scope.params.idcliente : 0;
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo !== undefined ? $scope.params.tipo : '1';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
            $scope.params.idtsventa = $scope.params.idtsventa != null && $scope.params.idtsventa !== undefined ? $scope.params.idtsventa : 0;
            if(+$scope.params.tipo === 4){ reporte = 'ByqyuVFnW'; }
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? '' : reporte, $scope.params).then(function(pdf){ $scope.content = pdf; });
        };       

    }]);

}());