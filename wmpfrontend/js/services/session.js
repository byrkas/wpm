angular.module('WhoPlayMusic').factory('Session', function SessionFactory($http, $location) {
  var sessionPromise = $http({method: 'GET', url: "/session"});

  return {
    sessionData: function() {
      return sessionPromise;
    },
    
    authenticate: function() {
      this.sessionData().then(function(sessionUser){
        if(!sessionUser || !sessionUser.data.id) {
          $location.path('/');
        }
      });
    }
  };
});
