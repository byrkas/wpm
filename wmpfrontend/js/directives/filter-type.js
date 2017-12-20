angular.module('WhoPlayMusic')
.directive('filterType', function() {
  return {
    replace: true,
    restrict: "E",
    scope: {
    	types: "=",
    	activeType: "="
    },
    controller: function($scope) {    	
    	$scope.isTouched = false;
    	$scope.linkClick = function()
    	{
    		$scope.isTouched = !$scope.isTouched;
    	}
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeType !== 0);
    	}
    	$scope.Delete = function(e) {
    		  $scope.$destroy();
    		}
    },
    templateUrl: '/templates/directives/filter-type.html',
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
      scope.resetType = function(){
    	  scope.activeType = 0;
      }
      scope.$on('$destroy', function () {
      	element.remove();
      	scope.Delete();
      });
    }
  };
});