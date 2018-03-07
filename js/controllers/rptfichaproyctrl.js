(function(){

    var rptfichaproyctrl = angular.module('cpm.rptfichaproyctrl', []);

    rptfichaproyctrl.controller('rptFichaProyCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc){

        $scope.params = {idproyecto: undefined};
        $scope.proyectos = [];
        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });

        var test = false;
        $scope.getRptFichaProy = function(){
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'BJGZI7Bkg' : 'SJuVsQByg', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {idproyecto: '0'}; };

    }]);
}());
