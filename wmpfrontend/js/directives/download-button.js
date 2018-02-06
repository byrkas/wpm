angular.module('WhoPlayMusic')
.directive('downloadButton', function() {
  return {
    restrict: "A",
    replace: true,
    scope: {
    	trackBuyButton: '@',
        track: "=",
    },    
    controller: function($scope, $http, $rootScope, $location, $window, $cookies){    	
    	$scope.downloading = false;
    	$scope.downloaded = false;
    	$scope.quoteSub = false;
    	$scope.lunch = false;
    	$scope.quote = [];
    	$scope.download = function(){
    		$rootScope.isLoading = true;
    		$http.get('http://api.djdownload.me/download/' + $scope.track.id, {
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
    					$http.get('http://api.djdownload.me/download-file-stream/' + $scope.track.id, {
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
    		    					
    		    					$cookies.put('globals', $rootScope.globals);
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
    		$http.get('http://api.djdownload.me/add-favorite/' + $scope.track.id, {
    			withCredentials: true,
    			headers : {
    				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
    				}
    			}).then(function(response){
    				$scope.track.isFavorite = true;
    			})
    	}
    	$scope.removeFavorite = function(){
    		$http.get('http://api.djdownload.me/remove-favorite/' + $scope.track.id, {
    			withCredentials: true,
    			headers : {
    				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
    				}
    			}).then(function(response){
    				$scope.track.isFavorite = false;
    			})
    	}
    },
    templateUrl: '/templates/directives/download-button.html',
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