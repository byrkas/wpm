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
    	$scope.Delete = function(e) {
		  $scope.$destroy();
		}
    },
    templateUrl: '/templates/directives/track-item.html',
    link: function(scope, element, attrs) {    	    	
    	/*element.find('.mobile-action').bind("click", function () {
        	element.toggleClass("opened-actions");
        });*/
    	
        scope.$on('$destroy', function () {
        	scope.Delete();
        	element.remove();
        });
    }
  };
}]);