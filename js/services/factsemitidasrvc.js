(function () {

    const service = angular.module('cpm.factemitidas', ['cpm.comunsrvc']);

    service.factory('factEmitidaSrvc', ['comunFact', (comunFact) => {
        const urlBase = 'php/rptfactemitidas.php';

        return {
            pendientes: function (params) {
                return comunFact.doPOST(urlBase + '/factspend', params);
            },
            eliminarCargo: function (obj) {
                return comunFact.doPOST(urlBase + '/elmcargo', obj);
            }
        };
    }]);

}());
