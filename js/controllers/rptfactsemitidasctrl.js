(function(){

    var rptfactsemitidasctrl = angular.module('cpm.rptfactsemitidasctrl', []);

    rptfactsemitidasctrl.controller('rptFacturasEmitidasCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'proyectoSrvc', 'tipoServicioVentaSrvc', 'tipoCambioSrvc', function($scope, empresaSrvc, jsReportSrvc, proyectoSrvc, tipoServicioVentaSrvc, tipoCambioSrvc){

        $scope.params = { 
            idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate(), cliente: '', tipo: '1', idcliente: 0, idproyecto: undefined,
            idtsventa: undefined, soloanuladas: 0, tc: undefined
        };
        $scope.empresas = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;
        $scope.proyectos = [];
        $scope.tsventa = [];

        empresaSrvc.lstEmpresas().then((d) => $scope.empresas = d);

        tipoServicioVentaSrvc.lstTSVenta().then((d) => $scope.tsventa = d);

        tipoCambioSrvc.getTC().then(() => tipoCambioSrvc.getLastTC().then((d) => $scope.params.tc = parseFloat(parseFloat(d.lasttc).toFixed(2))));

        $scope.loadProyectos = (idempresa) => proyectoSrvc.lstProyectosPorEmpresa(+idempresa).then((d) => $scope.proyectos = d);

        $scope.clienteSelected = (item) => {
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

        $scope.focusOut = (item) => {
            if(item == null || item === undefined || item.toString().trim() === ''){
                $scope.params.cliente = '';
                $scope.params.idcliente = 0;
            }
        };

        let test = false;
        $scope.getFactsEmitidas = () => {
            let reporte = 'BJW6LWoYb';
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa !== undefined ? $scope.params.idempresa : '';
            $scope.params.cliente = $scope.params.cliente != null && $scope.params.cliente !== undefined ? $scope.params.cliente : '';
            $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente !== undefined ? $scope.params.idcliente : 0;
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo !== undefined ? $scope.params.tipo : '1';
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto !== undefined ? $scope.params.idproyecto : 0;
            $scope.params.idtsventa = $scope.params.idtsventa != null && $scope.params.idtsventa !== undefined ? $scope.params.idtsventa : 0;
            $scope.params.soloanuladas = $scope.params.soloanuladas != null && $scope.params.soloanuladas !== undefined ? $scope.params.soloanuladas : 0;
            if(+$scope.params.tipo === 4){ reporte = 'ByqyuVFnW'; }
            if(+$scope.params.tipo === 5){reporte = 'B1nLiotFw'}
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? '' : reporte, $scope.params).then((pdf) => $scope.content = pdf);
        };       

    }]);

}());