(function(){

    var showtab = angular.module('cpm.showtab', []);

    showtab.directive('showTab', [function(){
        return {
            link: function (scope, element, attrs) {
                element.click(function (e) {
                    e.preventDefault();
                    $(element).tab('show');
                });
            }
        };
    }]);

}());
