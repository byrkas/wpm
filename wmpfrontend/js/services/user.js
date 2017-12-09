angular.module('WhoPlayMusic').factory('User', function UserFactory($http) {
  return {
    all: function() {
      return $http({method: 'GET', url: '/users'});
    },
    find: function(id){
      return $http({method:'GET', url: '/users/' + id});
    },
    update: function(userObj){
      return $http({method: 'PUT', url: '/users/' + userObj.id, data: userObj});
    }
  }
});
