(function(){
    
    const rptingegrproysrvc = angular.module('cpm.gerencialsrvc', ['cpm.comunsrvc']);

    rptingegrproysrvc.factory('gerencialSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/rptgerenciales.php';

        return {
            finanzas: (obj) => comunFact.doPOST(`${urlBase}/finanzas`, obj)
        };
    }]);

}());
