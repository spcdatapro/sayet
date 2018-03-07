(function(){

    var menusrvc = angular.module('cpm.menusrvc', ['cpm.comunsrvc']);

    menusrvc.factory('menuSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/menu.php';

        var menuSrvc = {
            getModulos: function(){
                return comunFact.doGET(urlBase + '/lstmodulos');
            },
            getMenu: function(idmodulo){
                return comunFact.doGET(urlBase + '/lstmenu/' + idmodulo);
            },
            getItems: function(idmenu){
                return comunFact.doGET(urlBase + '/lstitems/' + idmenu);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return menuSrvc;
    }]);

}());
