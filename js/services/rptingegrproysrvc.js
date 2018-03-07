(function(){
    
        var rptingegrproysrvc = angular.module('cpm.rptingegrproysrvc', ['cpm.comunsrvc']);
    
        rptingegrproysrvc.factory('rptIngresosEgresosProySrvc', ['comunFact', function(comunFact){
            var urlBase = 'php/rptingegrproy.php';
    
            return {
                resumen: function(obj){
                    return comunFact.doPOST(urlBase + '/resumen', obj);
                },
                detalle: function(obj){
                    return comunFact.doPOST(urlBase + '/detalle', obj);
                }
            };
        }]);
    
    }());
    