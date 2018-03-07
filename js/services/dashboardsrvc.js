(function(){

    var dashboardsrvc = angular.module('cpm.dashboardsrvc', ['cpm.comunsrvc']);

    dashboardsrvc.factory('dashBoardSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/dashboard.php';

        return {
            favUsr: function(idusr){
                return comunFact.doGET(urlBase + '/favusr/' + idusr);
            },
            favPendientes: function(idusr){
                return comunFact.doGET(urlBase + '/favspend/' + idusr);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            },
            rptActivosEmpresa: function(){
                return comunFact.doGET(urlBase + '/activosemp');
            },
            rptProyectosEmpresa: function(){
                return comunFact.doGET(urlBase + '/proyectosemp');
            }
        };

    }]);

}());

