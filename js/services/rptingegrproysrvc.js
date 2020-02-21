(function(){
    
        const rptingegrproysrvc = angular.module('cpm.rptingegrproysrvc', ['cpm.comunsrvc']);
    
        rptingegrproysrvc.factory('rptIngresosEgresosProySrvc', ['comunFact', (comunFact) => {
            const urlBase = 'php/rptingegrproy.php';
    
            return {
                resumen: (obj) => comunFact.doPOST(`${urlBase}/resumen`, obj),
                detalle: (obj) => comunFact.doPOST(`${urlBase}/detalle`, obj),
                ingegr: (obj) => comunFact.doPOST(`${urlBase}/ingegr`, obj),
            };
        }]);
    
    }());
    