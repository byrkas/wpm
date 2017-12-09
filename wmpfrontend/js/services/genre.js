angular.module('WhoPlayMusic').factory('Genre', function GenreFactory($http, $q) {
  var genres;
  
  return {
    all: function() {      
      var deferred = $q.defer();
      if(genres) {
        deferred.resolve(genres);
      } else {
        $http.get("http://api.wpm.zeit.style/genres")
        .then(
        function (response){
      	  genres = response.data;
          deferred.resolve(response.data);
        },function (error){
           deferred.reject(error);	
        });
      }
      
      return deferred.promise;
    }
  };
});
