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
    	$scope.isTouched = false;
    	$scope.linkClick = function()
    	{
    		$scope.isTouched = !$scope.isTouched;
    	}
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeGenre !== 0);
    	}      
    	$scope.Delete = function(e) {
    		  $scope.$destroy();
    		}
    },
    templateUrl: '/templates/directives/filter-genre.html',
    link: function(scope, element, attrs) {
    	$(document).bind('click', function(event){
            var isClickedElementChildOfPopup = element
                .find(event.target)
                .length > 0;

            if (isClickedElementChildOfPopup)
                return;

            scope.$apply(function(){
            	scope.isTouched = false;
            });
        });
    	scope.resetGenre = function(){
    	  scope.activeGenre = 0;
    	}
        scope.$on('$destroy', function () {
        	element.remove();
        	scope.Delete();
        });
    }
  };
});