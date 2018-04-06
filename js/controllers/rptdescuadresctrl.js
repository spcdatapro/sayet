(function(){

    var rptdescuadresctrl = angular.module('cpm.rptdescuadresctrl', []);

    rptdescuadresctrl.controller('rptDescuadresCtrl', ['$scope', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', function($scope, empresaSrvc, authSrvc, jsReportSrvc){

        $scope.params = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
            }
        });

        var test = false;
        $scope.getDescuadres = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'B1IqESgKM' : 'rkoWuBeYz', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        /*
         $scope.getDescuadresExcel = function(){
         $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
         $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
         jsReportSrvc.libroDiarioXlsx($scope.params).then(function(result){
         var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
         saveAs(file, 'LibroDeDiario.xlsx');
         });
         };
         */
    }]);
}());

