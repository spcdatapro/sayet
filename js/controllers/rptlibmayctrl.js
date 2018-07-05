(function(){

    var rptlibmayctrl = angular.module('cpm.rptlibmayctrl', []);

    rptlibmayctrl.controller('rptLibroMayorCtrl', ['$scope', 'rptLibroMayorSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', 'cuentacSrvc', function($scope, rptLibroMayorSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce, cuentacSrvc){

        $scope.params = {
            del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, codigo: undefined, constproc: 0, filtro: '1', codigoal: undefined, cuentasSel: undefined, vercierre: 0
        };
        $scope.libromayor = [];
        $scope.content = '';
        $scope.cuentas = [];
        $scope.empresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                cuentacSrvc.lstCuentasC($scope.params.idempresa).then(function(d){ $scope.cuentas = d; });
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        function setCodigos(ctas){
            var lista = '';
            ctas.forEach(function(cta){
                if(lista != ''){ lista += ','; }
                lista += "'" + cta.trim() + "'";
            });
            return lista;
        }

        $scope.resetParams = function(){
            $scope.params.cuentasSel = undefined;
            $scope.params.codigo = undefined;
            $scope.params.codigoal = undefined;
        };

        var test = false;
        $scope.getLibroMayor = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.vercierre = $scope.params.vercierre != null && $scope.params.vercierre !== undefined ? $scope.params.vercierre : 0;

            if(+$scope.params.filtro == 1){
                $scope.params.codigo = $scope.params.cuentasSel ? setCodigos($scope.params.cuentasSel) : '';
                $scope.params.codigoal = '';
            }else{
                $scope.params.codigo = $scope.params.codigo != null && $scope.params.codigo != undefined ? ("'" + $scope.params.codigo.trim() + "'") : '';
                $scope.params.codigoal = $scope.params.codigoal != null && $scope.params.codigoal != undefined ? ("'" + $scope.params.codigoal.trim() + "'") : '';
            }

            //console.log($scope.params); return;

            if(+$scope.params.constproc == 0){
                jsReportSrvc.getPDFReport(test ? '' : 'ryzIcT87Z', $scope.params).then(function(pdf){ $scope.content = pdf; });
            }else{
                jsReportSrvc.getPDFReport(test ? '' : 'rJsjLgzyG', $scope.params).then(function(pdf){ $scope.content = pdf; });
            }
        };

        $scope.getLibroMayorXLSX = function(){
            //$scope.params.constproc = 0;
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.vercierre = $scope.params.vercierre != null && $scope.params.vercierre !== undefined ? $scope.params.vercierre : 0;

            if(+$scope.params.filtro == 1){
                $scope.params.codigo = $scope.params.cuentasSel ? setCodigos($scope.params.cuentasSel) : '';
                $scope.params.codigoal = '';
            }else{
                $scope.params.codigo = $scope.params.codigo != null && $scope.params.codigo != undefined ? ("'" + $scope.params.codigo.trim() + "'") : '';
                $scope.params.codigoal = $scope.params.codigoal != null && $scope.params.codigoal != undefined ? ("'" + $scope.params.codigoal.trim() + "'") : '';
            }

            if(+$scope.params.constproc == 0){
                jsReportSrvc.getReport(test ? 'S1OS4Lhdf' : 'HJwukPndf', $scope.params).then(function(result){
                    //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                    var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                    saveAs(file, 'DMG_' + nombre + '.xlsx');
                });
            }else{
                jsReportSrvc.getReport(test ? 'rkv1lRrFM' : 'SkHHrArKG', $scope.params).then(function(result){
                    //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                    var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                    saveAs(file, 'INT_' + nombre + '.xlsx');
                });
            }


        };

    }]);

}());