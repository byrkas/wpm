angular.module('WhoPlayMusic').factory( 'Album', function($resource){  
  return $resource('http://api.wpm.zeit.style/album/:id');
});

angular.module('WhoPlayMusic').controller('AlbumIndexController', function($scope, $http, Album, $filter, $routeParams, $window, ngMeta) {
	$scope.album = {};
	$scope.tracks = [];
	$scope.url = $window.location.href;
	
	Album.get({id: $routeParams.id}, function(response){
		 $scope.album = response.album;
		 $scope.tracks = response.tracks;
		 
		 ngMeta.setTitle('Album '+$scope.album.name+' by '+$scope.album.artists[0].name);
		 ngMeta.setDefaultTag('og:title', 'Album '+$scope.album.name+' by '+$scope.album.artists[0].name);
		 ngMeta.setDefaultTag('twitter:title', 'Album '+$scope.album.name+' by '+$scope.album.artists[0].name);
		 ngMeta.setDefaultTag('og:image', $scope.album.cover);
		 ngMeta.setDefaultTag('twitter:image', $scope.album.cover);
		 ngMeta.setDefaultTag('og:url', $scope.url);
		 ngMeta.setDefaultTag('og:description', 'Download Now on Who Play Music.');
		 ngMeta.setDefaultTag('twitter:description', 'Download Now on Who Play Music.');
	 })
});
