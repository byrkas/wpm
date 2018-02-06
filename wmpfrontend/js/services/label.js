angular.module('WhoPlayMusic').factory('Label', function LabelFactory($http, $q) {
  var labels;
  
  return {
    all: function() {      
      var deferred = $q.defer();
      if(labels) {
        deferred.resolve(labels);
      } else {
        $http.get("http://api.djdownload.me/labels")
        .then(
        function (response){
        	labels = response.data;
        	deferred.resolve(response.data);
        },function (error){
           deferred.reject(error);	
        });
      }
      
      return deferred.promise;
    }
  };
});
