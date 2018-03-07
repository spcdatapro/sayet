(function(){

    var getbycodctafltr = angular.module('cpm.getbycodctafltr', []);

    getbycodctafltr.filter('getByCodCta', function() {
        return function(input, str) {
            var i=0, len=input.length;
            for (; i<len; i++) {
                if (input[i].codcta.toLowerCase().indexOf(str.toLowerCase()) > -1) {
                    return input[i];
                }
            }
            return null;
        }
    });

}());
