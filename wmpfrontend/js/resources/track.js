angular.module('WhoPlayMusic').factory('Track', function TrackFactory($resource) {  
  return $resource('http://api.wpm.zeit.style/tracks/:id', {}, {
    total: {
    	url: "http://api.wpm.zeit.style/tracks-total",
    	transformResponse: function(data){
    		return angular.fromJson(data).total;
    	}
    }
  });
});
