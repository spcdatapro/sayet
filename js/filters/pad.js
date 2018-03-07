(function(){

    var padfltr = angular.module('cpm.padfltr', []);

    padfltr.filter('padNumber', function() {
        return function (input, size) {
            var s = input + "";
            while (s.length < size) s = "0" + s;
            return s;
        }
    });

}());
