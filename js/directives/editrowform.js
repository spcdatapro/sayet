(function(){

    var editrowform = angular.module('cpm.editrowform', []);

    editrowform.directive('editRowForm',[function(){
        return{
            restrict: 'E',
            templateUrl: 'templates/editrowform.html',
            replace: true,
            scope: {
                editingRow: "=",
                updateFunc: "&",
                deleteFunc: "&"
            }
        };
    }]);

}());
