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
    controller: function($scope, $http, $rootScope, $location, $window, $cookieStore, $httpParamSerializer, $compile){
    	$scope.downloading = false;
    	$scope.downloaded = false;
    	$scope.quoteSub = false;
    	$scope.lunch = false;
    	$scope.quote = [];
    	$scope.isDownloaded = function(id){
    		return $rootScope.isDownloaded(id);
    	}
    	$scope.object = null;

    	$scope.download = function(format){
    		if($rootScope.globals.currentUser === undefined){
    			$location.path('/payment-page');
    			return;
    		}
    		$rootScope.isLoading = true;
    		$http.get('http://api.wpm.zeit.style/download/' + $scope.track.id+ '?token='+$rootScope.globals.currentUser.token).then(function(response){
    	    		$rootScope.isLoading = false;
    				if(!response.data.success){
    					if(response.data.messages != ''){
    		        		$rootScope.hasNotification = true;
    		        		$rootScope.notifMessage = response.data.messages;
    		        		$rootScope.notifType = 'failure';
    		    		}
    					$location.path('/payment-page');
    				}else{
    					$scope.quoteSub = response.data.quoteSub;
    					$scope.quote = response.data.quote;
    					$rootScope.quotes = response.data.quote;
    					var query = {token: $rootScope.globals.currentUser.token};
    					if(format !==undefined){
    						query.format = format;
    					}

	    				$rootScope.hasNotification = true;
		        		$rootScope.notifMessage = "You are now downloading \"" + $scope.track.title + "\".";
		        		$rootScope.notifType = 'success';

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
                    	$window.location = 'http://api.wpm.zeit.style/download-file-stream/' + $scope.track.id +'?'+ $httpParamSerializer(query);
    				}
    			})
    	}
    	$scope.lunchMenu = function(){
    		if($cookieStore.get('globals')){
    			$scope.lunch = !$scope.lunch;
        		if($scope.lunch == true){
        			var width = angular.element($window).width();
        			if(width < 1140){
        				$scope.object = angular.element('<div id="modal" class="modal">'+
        				  '<div class="modal-body modal-body-multi-cart">'+
        				    '<div class="modal-title-bar">'+
        				      '<h1 class="cart-settings-title"></h1>'+
        				      '<a ng-click="removeObject()" class="close-modal-link icon icon-delete"></a>'+
        				    '</div>'+
        				    '<div class="modal-main-content">'+
        				    '<div class="buy-button-menu">'+
        				    '<ul class="cart-list">'+
        				    '<li ng-click="addFavorite();removeObject();" ng-show="!track.isFavorite"><span class="title"><svg viewBox="0 0 200 200" class="cart-menu-default-icon"><use xlink:href="/static/images/defs.svg#icon-star"></use></svg>Add to favorites</span></li>'+
        				    '<li ng-click="removeFavorite();removeObject();" ng-show="track.isFavorite"><span class="title"><svg viewBox="0 0 200 200" class="cart-menu-default-icon"><use xlink:href="/static/images/defs.svg#icon-star"></use></svg>Remove from favorites</span></li>'+
        				    '</ul></div>'+
        				    '</div>'+
        				  '</div>'+
        				'</div>');
        				angular.element('footer').append($scope.object);
        				$compile($scope.object)($scope);
        			}
        		}
    		}
    	}
    	$scope.removeObject = function(){
    		$scope.object = null;
    		angular.element('#modal').remove();
    	}
    	$scope.addFavorite = function(){
    		$http.get('http://api.wpm.zeit.style/add-favorite/' + $scope.track.id + '?token='+$rootScope.globals.currentUser.token).then(function(response){
    				$scope.track.isFavorite = true;
    			})
    	}
    	$scope.removeFavorite = function(){
    		$http.get('http://api.wpm.zeit.style/remove-favorite/' + $scope.track.id + '?token='+$rootScope.globals.currentUser.token).then(function(response){
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