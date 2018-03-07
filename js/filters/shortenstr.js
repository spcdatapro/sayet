(function(){

    var shortenstrfltr = angular.module('cpm.shortenstrfltr', []);

    shortenstrfltr.filter('shortenStr', function() {
        return function (input, size) {
            if(input != null && input != undefined){
                if(input.length > size){
                    var tmp = input.substring(0, (+size - 3));
                    return tmp + '...';
                }else{
                    return input;
                }
            }
            return '';
        }
    });

}());

