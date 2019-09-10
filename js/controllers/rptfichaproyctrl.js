(function(){

    var rptfichaproyctrl = angular.module('cpm.rptfichaproyctrl', []);

    rptfichaproyctrl.controller('rptFichaProyCtrl', ['$scope', 'empresaSrvc', 'proyectoSrvc', 'jsReportSrvc', '$filter', function($scope, empresaSrvc, proyectoSrvc, jsReportSrvc, $filter){

        $scope.params = {idproyecto: undefined};
        $scope.proyectos = [];
        proyectoSrvc.lstProyecto().then(function(d){ $scope.proyectos = d; });
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        var test = false;
        $scope.getRptFichaProy = function(){
            jsReportSrvc.getPDFReport(test ? 'BJGZI7Bkg' : 'SJuVsQByg', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getRptFichaProyExcel = function(){
            jsReportSrvc.getReport(test ? 'B16trDNA7' : 'B16trDNA7', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                saveAs(file, 'Ficha_Proyecto_' + $filter('padNumber')(+$scope.params.idproyecto, 5) + '.xlsx');
            });
        };

        $scope.getRptOcupaProy = function(){
            jsReportSrvc.getPDFReport(test ? 'S1d6O1moz' : 'rk1aSNQoz', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.getRptOcupaProyExcel = function(){
            jsReportSrvc.getReport(test ? 'B1uUpwEAQ' : 'B1uUpwEAQ', $scope.params).then(function(result){
                var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                saveAs(file, 'Ocupacion_Proyecto_' + $filter('padNumber')(+$scope.params.idproyecto, 5) + '.xlsx');
            });
        };

        $scope.resetParams = function(){ $scope.params = {idproyecto: undefined}; };

    }]);
}());
