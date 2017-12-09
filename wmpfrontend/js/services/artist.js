angular.module('WhoPlayMusic').factory('Artist', function ArtistFactory($http, $q) {
  var artists;
  
  return {
    all: function() {
      var deferred = $q.defer();
      if(artists) {
        deferred.resolve(artists);
      } else {
        $http.get("http://api.wpm.zeit.style/artists")
        .then(
        function (response){
        	  artists = response.data;
              deferred.resolve(response.data);
        },function (error){
           deferred.reject(error);	
        });
      }
      
      return deferred.promise;
    }
  };
});
