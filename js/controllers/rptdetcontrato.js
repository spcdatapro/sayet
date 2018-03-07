(function(){

    var rptdetcontratoctrl = angular.module('cpm.rptdetcontratoctrl', []);

    rptdetcontratoctrl.controller('rptDetContratoCtrl', ['$scope', 'clienteSrvc', 'tipoServicioVentaSrvc', 'jsReportSrvc', function($scope, clienteSrvc, tipoServicioVentaSrvc, jsReportSrvc){

        $scope.params = { idcontrato: undefined, del: null, al: null, idtiposervicio: '0' };
        $scope.contratos = [];
        $scope.fltrtsventa = [];
        clienteSrvc.rptDetContrato().then(function(d){ $scope.contratos = d; });

        tipoServicioVentaSrvc.lstTSVenta().then(function(d){
            $scope.fltrtsventa = d;
            $scope.fltrtsventa.push({id: "0", desctiposervventa: "Todos"});
        });

        var test = false;
        $scope.getRptDetContrato = function(){
            $scope.params.delstr = moment($scope.params.del).isValid() ? moment($scope.params.del).format('YYYY-MM-DD') : '0';
            $scope.params.alstr = moment($scope.params.al).isValid() ? moment($scope.params.al).format('YYYY-MM-DD') : '0';
            $scope.params.idtiposervicio = $scope.params.idtiposervicio != null && $scope.params.idtiposervicio != undefined ? $scope.params.idtiposervicio : '0';
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'SywTrJEJx' : 'HkPQ-mSkl', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = { idcontrato: undefined, del: null, al: null, idtiposervicio: '0' }; };

    }]);
}());