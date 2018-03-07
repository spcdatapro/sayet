(function(){

    var datatablewrapper = angular.module('cpm.datatablewrapper', []);

    datatablewrapper.directive('datatableWrapper', ['$timeout', '$compile', function($timeout, $compile){
        return {
            restrict: 'E',
            transclude: true,
            template: '<ng-transclude></ng-transclude>',
            link: link
        };

        function link(scope, element) {
            // Using $timeout service as a "hack" to trigger the callback function once everything is rendered
            $timeout(function () {
                // Compiling so that angular knows the button has a directive
                $compile(element.find('edit-row-form'))(scope);
                //alert('Encontrado!');
            }, 0, false);
        }

    }]);

}());
