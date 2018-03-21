(function(){

    var rptestresctrl = angular.module('cpm.rptestresctrl', []);

    rptestresctrl.controller('rptEstadoResultadosCtrl', ['$scope', 'rptEstadoResultadosSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', function($scope, rptEstadoResultadosSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, acumulado: 0, nivel: '7', resAn: moment().year()};
        $scope.estadoresultados = [];
        $scope.empresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        var test = false;
        $scope.getEstRes = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.acumulado = $scope.params.acumulado != null && $scope.params.acumulado != undefined ? $scope.params.acumulado : 0;
			if(+$scope.params.acumulado == 1){
                $scope.params.fdelstr = moment($scope.params.al).format('YYYY') + '-01-01';
            }
            jsReportSrvc.getPDFReport(test ? '' : 'SJZw5yvm-', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getEstResXLSX = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.acumulado = $scope.params.acumulado != null && $scope.params.acumulado != undefined ? $scope.params.acumulado : 0;
            if(+$scope.params.acumulado == 1){
                $scope.params.fdelstr = moment($scope.params.al).format('YYYY') + '-01-01';
            }

            jsReportSrvc.getReport(test ? 'r1Ep8Kn_z' : 'HJsxKK3_z', $scope.params).then(function(result){
                //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.fdelstr).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                saveAs(file, 'ER_' + nombre + '.xlsx');
            });
        };

        $scope.getEstResA = function(){
            $scope.params.fdelstr = $scope.params.resAn ;
            $scope.params.falstr = $scope.params.resAn ;
            //console.log('datos cont', $scope.params);
            jsReportSrvc.getReport(test ? 'SyQ63C8Mz' : 'SyQ63C8Mz', $scope.params).then(function(result){
                //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                var nombre = $scope.empresa.abreviatura + '_' + $scope.params.resAn;
                saveAs(file, 'ER_' + nombre + '.xlsx');
            });
        };

    }]);

}());
