(function(){
    
        var txtcleanerfltr = angular.module('cpm.txtcleanerfltr', []);
    
        txtcleanerfltr.filter('textCleaner', function() {
            return function (input) {
                if(input != null && input != undefined){
                    //var tmp = input.replace(/[^\w\s\.]/gi, '');
                    var tmp = input.replace(/[^A-Za-z0-9.\-]/gi, '');
                    return tmp.replace(/ /g, '');
                }
                return '';
            }
        });
    
    }());
    