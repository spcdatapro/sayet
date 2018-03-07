(function(){

    var beneficiariosrvc = angular.module('cpm.beneficiariosrvc', ['cpm.comunsrvc']);

    beneficiariosrvc.factory('beneficiarioSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/beneficiario.php';

        var beneficiarioSrvc = {
            lstBeneficiarios: function(){
                return comunFact.doGET(urlBase + '/lstbene');
            },
            getBeneficiario: function(idbene){
                return comunFact.doGET(urlBase + '/getbene/' + idbene);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return beneficiarioSrvc;
    }]);

}());
