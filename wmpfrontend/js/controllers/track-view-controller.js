angular.module('WhoPlayMusic').factory( 'Track', function($resource){  
  return $resource('http://api.wpm.zeit.style/track/:id');
});

angular.module('WhoPlayMusic').controller('TrackViewController', function($scope, $http, Track, $filter, $routeParams, $window, $rootScope, $location, ngMeta) {
	$scope.track = {};
	$scope.url = $window.location.href;
		
	var siteModeListener = function(newValue, oldValue, scope){
		if(newValue == true && !$rootScope.siteModeShow()){
			$location.path('/account/login');
		}
	  }
	$rootScope.$watch('parseSiteMode',siteModeListener);
	
	var showPromo = true;
	if($rootScope.globals.currentUser){
		showPromo = $rootScope.globals.currentUser.quotes.showPromo;
	}
	Track.get({id: $routeParams.id, showPromo: showPromo}, function(response){
		 $scope.track = response.track;	 
		 
		 ngMeta.setTitle($scope.track.title + ' by '+ $scope.track.artists[0].name);
		 ngMeta.setDefaultTag('og:title', $scope.track.title + ' by '+ $scope.track.artists[0].name);
		 ngMeta.setDefaultTag('twitter:title', $scope.track.title + ' by '+ $scope.track.artists[0].name);
		 ngMeta.setDefaultTag('og:image', $scope.track.cover);
		 ngMeta.setDefaultTag('twitter:image', $scope.track.cover);
		 ngMeta.setDefaultTag('og:url', $scope.url);
		 ngMeta.setDefaultTag('og:description', 'Download Now on Who Play Music.');
		 ngMeta.setDefaultTag('twitter:description', 'Download Now on Who Play Music.');
	 })
});
