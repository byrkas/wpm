angular.module('WhoPlayMusic').factory('Track', function TrackFactory($resource) {  
  return $resource('http://api.djdownload.me/tracks/:id', {}, {
    total: {
    	url: "http://api.djdownload.me/tracks-total",
    	transformResponse: function(data){
    		return angular.fromJson(data).total;
    	}
    }
  });
});
