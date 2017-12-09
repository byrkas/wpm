angular.module('WhoPlayMusic')
.directive('trackItem', ['$cookies', function($cookies) {
  return {
    restrict: "A",
    scope: {
        track: "=",
    },    
    controller: function($scope, $rootScope, $cookies){
    	$scope.isLogged = function(){
    		return $rootScope.isLogged();
    	}
    },
    templateUrl: '/templates/directives/track-item.html',
    link: function(scope, element, attrs, $cookies) {    	
    	var mobileLink = element.find('.mobile-action');
    	angular.forEach(mobileLink, function(link){
            var linkEl = angular.element(link);
            linkEl.bind("click", function () {
            	element.toggleClass("opened-actions");
            });
        })
    }
  };
}]);