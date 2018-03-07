(function(){

  var getbyidfltr = angular.module('cpm.getbyidfltr', []);

  getbyidfltr.filter('getById', function() {
    return function(input, id) {
      var i=0, len=input.length;
      for (; i<len; i++) {
        if (+input[i].id == +id) {
          return input[i];
        }
      }
      return null;
    }
  });

}());