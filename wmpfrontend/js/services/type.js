angular.module('WhoPlayMusic').factory('Type', function TypeFactory($http, $q) {
  var types;
  
  return {
    all: function() {      
      var deferred = $q.defer();
      if(types) {
        deferred.resolve(types);
      } else {
        $http.get("http://api.wpm.zeit.style/types")
        .then(
        function (response){
        	types = response.data;
        	deferred.resolve(response.data);
        },function (error){
           deferred.reject(error);	
        });
      }
      
      return deferred.promise;
    }
  };
});
