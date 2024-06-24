(function(){
    
    const rptingegrproysrvc = angular.module('cpm.gerencialsrvc', ['cpm.comunsrvc']);

    rptingegrproysrvc.factory('gerencialSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/rptgerenciales.php';

        return {
            resumen: (obj) => comunFact.doPOST(`${urlBase}/resumen`, obj),
            detalle: (obj) => comunFact.doPOST(`${urlBase}/detalle`, obj),
            ingegr: (obj) => comunFact.doPOST(`${urlBase}/ingegr`, obj),
            getRes: (obj) => comunFact.doPOST(`${urlBase}/res`, obj)
        };
    }]);

}());
