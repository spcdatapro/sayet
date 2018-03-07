(function(){

    var dropdigitsfltr = angular.module('cpm.dropdigitsfltr', []);

    dropdigitsfltr.filter('dropDigits', function() {
        return function(floatNum, tam) {
            return String(floatNum)
                .split('.')
                .map(function (d, i) { return i ? d.substr(0, tam) : d; })
                .join('.');
        };
    });

}());
