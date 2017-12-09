angular.module('WhoPlayMusic')
.directive('filterGenre', function() {
  return {
    replace: true,
    restrict: "E",
    scope: {
    	genres: "=",
    	activeGenre: "=",
    },
    controller: function($scope) {  	
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeGenre !== 0);
    	}      
    },
    templateUrl: '/templates/directives/filter-genre.html',
    link: function(scope, element, attrs) {
    	scope.resetGenre = function(){
    	  scope.activeGenre = 0;
    	}
    }
  };
});