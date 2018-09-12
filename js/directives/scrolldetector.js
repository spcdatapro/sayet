(function(){

    var scrolldetector = angular.module('cpm.scrolldetector', []);

    scrolldetector.directive('scrollDetector', [function(){
        return {
            restrict: 'A',
            link: function (scope, element, attrs) {
                var raw = element[0];
                element.bind('scroll', function () {
                    if (raw.scrollTop + raw.offsetHeight >= raw.scrollHeight) {
                        //console.log("end of list");
                        scope.$apply(attrs.scrollDetector);
                    }
                });
            }
        };
    }]);
}());
