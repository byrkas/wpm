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
    	$scope.isActive = function()
    	{    		
    		return ($scope.activeType !== 0);
    	}
    },
    templateUrl: '/templates/directives/filter-type.html',
    link: function(scope, element, attrs) {
      scope.resetType = function(){
    	  scope.activeType = 0;
      }
    }
  };
});