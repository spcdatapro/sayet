(function(){

    var rptcatprovctrl = angular.module('cpm.rptcatprovctrl', ['cpm.proveedorsrvc']);

    rptcatprovctrl.controller('rptCatProvCtrl', ['$scope', 'proveedorSrvc', 'empresaSrvc', 'authSrvc', function($scope, proveedorSrvc, empresaSrvc, authSrvc){

        $scope.losProvs = [];
        $scope.objEmpresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function(r){
                    $scope.objEmpresa = r[0];
                });
            }
        });

        $scope.getLstProveedores = function(){
            proveedorSrvc.lstProveedores().then(function(d){
                $scope.losProvs = d;
                for(var i = 0; i < $scope.losProvs.length; i++){
                    $scope.losProvs[i].retensionisr = parseInt($scope.losProvs[i].retensionisr);
                    $scope.losProvs[i].diascred = parseInt($scope.losProvs[i].diascred);
                    $scope.losProvs[i].limitecred = parseFloat($scope.losProvs[i].limitecred);
                    $scope.losProvs[i].pequeniocont = parseInt($scope.losProvs[i].pequeniocont);
                    $scope.losProvs[i].detcont = [];
					/*
                    $.ajax({
                        dataType: "json",
                        type: "GET",
                        async: false,
                        url: 'php/proveedor.php/detcontprov/' + $scope.losProvs[i].id,
                        success: function (result) {
                            $scope.losProvs[i].detcont = result;
                        }
                    });
					*/
                    //proveedorSrvc.lstDetCuentaC(parseInt($scope.losProvs[i].id)).then(function(det){ $scope.losProvs[i].detcont = det; });
                }
            });
        };

        $scope.getLstProveedores();

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Catálogo de proveedores');
        };
    }]);

}());
