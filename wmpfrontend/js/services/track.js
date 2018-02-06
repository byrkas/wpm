angular.module('WhoPlayMusic').factory('Track', function TrackFactory($http, $q) {
  var tracks;
	
  return {
    all: function() {
      var deferred = $q.defer();
      if(tracks) {
        deferred.resolve(tracks);
      } else {
        $http.get("http://api.djdownload.me/tracks")
        .then(
        function (response){
        	tracks = response.data;
            deferred.resolve(response.data);
        },function (error){
           deferred.reject(error);	
        });
      }
      
      return deferred.promise;
    },
    find: function(id){
      return $http({method:'GET', url: 'http://api.djdownload.me/tracks/' + id});
    },
  };
});
