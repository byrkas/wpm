angular.module('WhoPlayMusic').factory( 'Album', function($resource){
  return $resource('http://api.wpm.zeit.style/album/:id');
});

angular.module('WhoPlayMusic').controller('AlbumIndexController', function($scope, $http, Album, $filter, $routeParams, $rootScope, $window, ngMeta, $location, $cookieStore) {
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

	 $scope.downloadArchive = function() {
		$rootScope.isLoading = true;
		$http.get('http://api.wpm.zeit.style/download-album/' + $routeParams.id, {
			withCredentials: true,
			headers : {
				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
				}
			}).then(function(response){
	    		$rootScope.isLoading = false;
				if(!response.data.success){
					$location.path('/payment-page');
				}else{
					$scope.downloading = true;
					$scope.quoteSub = response.data.quoteSub;
					$scope.quote = response.data.quote;
					$http.get('http://api.wpm.zeit.style/download-album-stream/' + $routeParams.id, {
						responseType: "arraybuffer",
		    			withCredentials: true,
		    			headers : {'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token}
		    			}).then(function(response){
		    				$scope.downloading = false;
		    				$scope.downloaded = true;
		    				if($scope.quoteSub && $scope.quote.length > 0){
		    					$rootScope.globals.currentUser.quotes.quotePromo = $scope.quote.quotePromo;
		    					$rootScope.globals.currentUser.quotes.quoteExclusive = $scope.quote.quoteExclusive;

		    					$cookieStore.put('globals', $rootScope.globals);
		    					$rootScope.quotes = $scope.quote;
		    				}
		    				var data = response.data;
			 				var filename = response.headers('X-filename');

			 		        var contentType = response.headers('content-type');
			 		        var linkElement = document.createElement('a');
			 		        try {
			 		            var blob = new Blob([data], { type: contentType });
			 		            var url = window.URL.createObjectURL(blob);

			 		            linkElement.setAttribute('href', url);
			 		            linkElement.setAttribute("download", filename);

			 		            var clickEvent = new MouseEvent("click", {
			 		                "view": $window,
			 		                "bubbles": true,
			 		                "cancelable": false
			 		            });
			 		            linkElement.dispatchEvent(clickEvent);
			 		        } catch (ex) {
			 		            console.log(ex);
			 		        }

		    			},function(error){
		    				$scope.downloading = false;
		    			})
				}
			})
	};
});
