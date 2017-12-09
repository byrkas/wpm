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
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeLabel !== 0);
    	}        
    },
    templateUrl: '/templates/directives/filter-label.html',
    link: function(scope, element, attrs) {
    	scope.resetLabel = function(){
        	  scope.activeLabel = 0;
          }
    }
  };
});