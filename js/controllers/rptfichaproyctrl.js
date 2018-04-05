(function(){

    var rptfichaproyctrl = angular.module('cpm.rptfichaproyctrl', []);

    rptfichaproyctrl.controller('rptFichaProyCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc){

        $scope.params = {idproyecto: undefined};
        $scope.proyectos = [];
        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });

        var test = false;
        $scope.getRptFichaProy = function(){
            jsReportSrvc.getPDFReport(test ? 'BJGZI7Bkg' : 'SJuVsQByg', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getRptOcupaProy = function(){
            jsReportSrvc.getPDFReport(test ? 'S1d6O1moz' : 'rk1aSNQoz', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.resetParams = function(){ $scope.params = {idproyecto: undefined}; };

    }]);
}());
