angular.module('WhoPlayMusic')
.directive('downloadButtons', function() {
  return {
    restrict: "A",
    scope: {
    	trackBuyButton: '@',
    	isLogged: '@',
    	isDownloaded: '@',
        track: "=",
    },
    controller: function($scope, $http, $rootScope, $location, $window, $cookieStore){
    	$scope.downloading = false;
    	$scope.downloaded = false;
    	$scope.quoteSub = false;
    	$scope.lunch = false;
    	$scope.quote = [];
    	$scope.isDownloaded = function(id){
    		return $rootScope.isDownloaded(id);
    	}
    	
    	$scope.download = function(format){
    		if($rootScope.globals.currentUser === undefined){
    			$location.path('/payment-page');
    			return;
    		}
    		$rootScope.isLoading = true;
    		$http.get('http://api.wpm.zeit.style/download/' + $scope.track.id, {
    			withCredentials: true,
    			headers : {
    				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
    				}
    			}).then(function(response){
    	    		$rootScope.isLoading = false;
    				if(!response.data.success){
    					if(response.data.messages != ''){
    		        		$rootScope.hasNotification = true;
    		        		$rootScope.notifMessage = response.data.messages;
    		        		$rootScope.notifType = 'failure';
    		    		}
    					$location.path('/payment-page');
    				}else{
    					$scope.downloading = true;
    					$scope.quoteSub = response.data.quoteSub;
    					$scope.quote = response.data.quote;
    					$rootScope.quotes = response.data.quote;
    					var query = {};
    					if(format !==undefined){
    						query.format = format;
    					}

	    				$rootScope.hasNotification = true;
		        		$rootScope.notifMessage = "You are now downloading \"" + $scope.track.title + "\".";
		        		$rootScope.notifType = 'success';
		        		
    					$http.get('http://api.wpm.zeit.style/download-file-stream/' + $scope.track.id, {
    					  	params : query,
    						responseType: "arraybuffer",
    		    			withCredentials: true,
    		    			headers : {'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token}
    		    			}).then(function(response){
    		    				$scope.downloading = false;
    		    				$scope.downloaded = true;
    		    				if($scope.quoteSub){
    		    					if($scope.quote.type == 'quotePromo')
    		    						$rootScope.globals.currentUser.quotes.quotePromo = $scope.quote.value;
    		    					else
    		    						$rootScope.globals.currentUser.quotes.quoteExclusive = $scope.quote.value;

    		    					$cookieStore.put('globals', $rootScope.globals);
    		    				}
    		    				
    		    				var tmp = $cookieStore.get('downloaded') || [];
                            	if(tmp.indexOf($scope.track.id) < 0){
                            		tmp.push($scope.track.id);
                            		$cookieStore.put('downloaded', tmp);
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
    		    		                "view": window,
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
    	}
    	$scope.lunchMenu = function(){
    		$scope.lunch = !$scope.lunch;
    	}
    	$scope.addFavorite = function(){
    		$http.get('http://api.wpm.zeit.style/add-favorite/' + $scope.track.id, {
    			withCredentials: true,
    			headers : {
    				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
    				}
    			}).then(function(response){
    				$scope.track.isFavorite = true;
    			})
    	}
    	$scope.removeFavorite = function(){
    		$http.get('http://api.wpm.zeit.style/remove-favorite/' + $scope.track.id, {
    			withCredentials: true,
    			headers : {
    				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
    				}
    			}).then(function(response){
    				$scope.track.isFavorite = false;
    			})
    	}
    },
    templateUrl: '/templates/directives/download-buttons.html',
    link: function(scope, element, attrs) {
    	$(document).bind('click', function(event){
            var isClickedElementChildOfPopup = element
                .find(event.target)
                .length > 0;

            if (isClickedElementChildOfPopup)
                return;

            scope.$apply(function(){
                scope.lunch = false;
            });
        });
    }
  };
});