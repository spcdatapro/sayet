(function(){

    var setfocusoncontrol = angular.module('cpm.setfocusoncontrol', []);

    setfocusoncontrol.directive('focusOnControl', [function(){
        return {
            restrict: 'A',
            link: function (scope, elem, attr) {
                elem.bind('keydown', function(e) {
                    var code = e.keyCode || e.which;
                    if (code === 13) {
                        e.preventDefault();
                        //elem.next().focus();
                        //console.log(attr);
                        var idx = parseFloat(attr.index);
                        //console.log(idx);
                        $('[data-index="' + (idx + 1).toString() + '"]').focus();
                    }
                });
            }
        };
    }]);

}());
