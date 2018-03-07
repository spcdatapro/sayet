(function(){

    var frmbtns = angular.module('cpm.frmbtns', []);

    frmbtns.directive('formButtons',[function(){
        return{
            restrict: 'E',
            templateUrl: 'templates/frmbtns.html',
            replace: true,
            scope: {
                vis: "=",    //Button visibility object
                btnDis: "=", //Button diabled
                obj: "=",    //objeto a usar
                uf: "&",     //update function
                df: "&",     //delete function
                nf: "&",     //reset function
                cf: "&",     //cancel function
                ef: "&",     //edit function
                pf: "&"      //print function
            }
        };
    }]);

}());

