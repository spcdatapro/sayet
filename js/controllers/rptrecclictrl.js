(function(){

    var rptrecclictrl = angular.module('cpm.rptrecclictrl', []);

    rptrecclictrl.controller('rptRecibosClienteCtrl', ['$scope', 'jsReportSrvc', 'empresaSrvc', 'monedaSrvc', 'clienteSrvc', 'proyectoSrvc', 'authSrvc', function($scope, jsReportSrvc, empresaSrvc, monedaSrvc, clienteSrvc, proyectoSrvc, authSrvc){

        $scope.params = {
            fdel: moment().startOf('month').toDate(), fal:moment().endOf('month').toDate(), serie: undefined, idempresa: 0, porempresa: 1, 
            idcliente: undefined, idproyecto: undefined, tipo: 2
        };

        $scope.empresas = [];
        $scope.monedas = [];
        $scope.clientes = [];
        $scope.proyectos = [];
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        authSrvc.getSession().then(function (usuario) {
            // traer empresas permitidas por el usuario
            empresaSrvc.lstEmpresas().then(function(d) { 
                empresaSrvc.getEmpresaUsuario(usuario.uid).then(function (autorizado) {
                    let idempresas = [];
                    autorizado.forEach(aut => {
                        idempresas.push(aut.id);
                    });
                    $scope.empresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                }); 
            });
        });        monedaSrvc.lstMonedas().then(function(d){ $scope.monedas = d; });
        clienteSrvc.lstCliente().then(function(d) { $scope.clientes = d; });
        $scope.loadProyectos = (idempresa) => proyectoSrvc.lstProyectosPorEmpresa(idempresa).then((d) => $scope.proyectos = d);

        $scope.getDataEmpresa = function(item)
        {
            $scope.loadProyectos(item.id);
            // console.log(item.id);
        };

        var test = false;

        $scope.getRptRecibosCliente = function(){
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            $scope.params.serie = $scope.params.serie != null && $scope.params.serie != undefined ? $scope.params.serie : '';
            $scope.params.idempresa = $scope.params.idempresa != null && $scope.params.idempresa != undefined ? $scope.params.idempresa : 0;
            $scope.params.anulados = $scope.params.anulados != 0 ? $scope.params.anulados : 0;
            $scope.params.idcliente = $scope.params.idcliente != null && $scope.params.idcliente != undefined ? $scope.params.idcliente : 0;
            $scope.params.idproyecto = $scope.params.idproyecto != null && $scope.params.idproyecto != undefined ? $scope.params.idproyecto : 0;

            // console.log($scope.params); return;
            var rpttest = 'Sk_TXxlMQ', rpt = 'B1spoxxfm';


            jsReportSrvc.getPDFReport(test ? rpttest : rpt, $scope.params).then(function(pdf){ $scope.content = pdf; });
            $scope.loadProyectos($scope.params.idempresa);
        };

    }]);
}());