(function(){

    var tipomovtranbansrvc = angular.module('cpm.tipomovtranbansrvc', ['cpm.comunsrvc']);

    tipomovtranbansrvc.factory('tipoMovTranBanSrvc', ['comunFact', function(comunFact){
        var urlBase = 'php/tipomovtranban.php';

        var tipoMovTranBanSrvc = {
            lstTiposMovTB: function(){
                return comunFact.doGET(urlBase + '/lsttiposmov');
            },
            getTipoMovTB: function(idtipomov){
                return comunFact.doGET(urlBase + '/gettipomov/' + idtipomov);
            },
            getByAbreviatura: function(qAbreviatura){
                return comunFact.doGET(urlBase + '/getbyabrevia/' + qAbreviatura);
            },
            getBySuma: function(suma){
                return comunFact.doGET(urlBase + '/getbysuma/' + suma);
            },
            editRow: function(obj, op){
                return comunFact.doPOST(urlBase + '/' + op, obj);
            }
        };
        return tipoMovTranBanSrvc;
    }]);

}());
