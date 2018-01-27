angular.module('WhoPlayMusic')
.directive('filterLabel', function() {
  return {
    replace: true,
    restrict: "E",
    scope: {
    	labels: "=",
    	activeLabel: "="
    },
    controller: function($scope) { 	
    	$scope.isTouched = false;
    	$scope.linkClick = function()
    	{
    		$scope.isTouched = !$scope.isTouched;
    	}
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeLabel !== 0);
    	}     
    	$scope.Delete = function(e) {
    		  $scope.$destroy();
    		}   
    },
    templateUrl: '/templates/directives/filter-label.html',
    link: function(scope, element, attrs) {
    	$(document).on('click', function(event){
            var isClickedElementChildOfPopup = element
                .find(event.target)
                .length > 0;

            if (isClickedElementChildOfPopup)
                return;

            scope.$apply(function(){
            	scope.isTouched = false;
            });
        });
    	scope.resetLabel = function(){
        	  scope.activeLabel = 0;
          }
        scope.$on('$destroy', function () {
        	element.remove();
        	scope.Delete();
        	$(document).off('click');
        });
    }
  };
});