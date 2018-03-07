(function(){

    var rptauditoriactrl = angular.module('cpm.rptauditoriactrl', []);

    rptauditoriactrl.controller('rptAuditoriaCtrl', ['$scope', 'jsReportSrvc', function($scope, jsReportSrvc){

        $scope.bitacoras = [];
        $scope.params = {usuario: "", tabla: "", tipo: "", fdelstr: "", falstr: "", descripcion: "", fdel: moment().startOf('month').toDate(), fal:moment().endOf('month').toDate()};

        var test = false;

        $scope.getRptAuditoria = function(){
            $scope.params.usuario = $scope.params.usuario != null && $scope.params.usuario != undefined ? $scope.params.usuario : '';
            $scope.params.tabla = $scope.params.tabla != null && $scope.params.tabla != undefined ? $scope.params.tabla : '';
            $scope.params.tipo = $scope.params.tipo != null && $scope.params.tipo != undefined ? $scope.params.tipo : '';
            $scope.params.descripcion = $scope.params.descripcion != null && $scope.params.descripcion != undefined ? $scope.params.descripcion : '';
            $scope.params.fdelstr = moment($scope.params.fdel).isValid() ? moment($scope.params.fdel).format('YYYY-MM-DD') : '';
            $scope.params.falstr = moment($scope.params.fal).isValid() ? moment($scope.params.fal).format('YYYY-MM-DD') : '';
            console.log($scope.params);
            //return;
            jsReportSrvc.getPDFReport(test ? 'BJC3IJlWe' : 'SywIDRx-x', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

    }]);
}());
