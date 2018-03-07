(function(){

    var onfinishrender = angular.module('cpm.onfinishrender', []);

    onfinishrender.directive('onFinishRender', ['$timeout', function($timeout){
        return {
            restrict: 'A',
            link: function (scope, element, attr) {
                if (scope.$last === true) {
                    $timeout(function () {
                        scope.$emit(attr.onFinishRender);
                    });
                }
            }
        };
    }]);

}());