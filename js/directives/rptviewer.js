(function(){

    const rptviewer = angular.module('cpm.rptviewer', []);

    rptviewer.directive('rptViewer',[function(){
        return{
            restrict: 'E',
            templateUrl: 'templates/rptviewer.html',
            replace: true,
            scope: {
                contenido: "="
            }
        };
    }]);

}());
