angular.module('WhoPlayMusic')
.directive('filterDate', function() {
  return {
    replace: true,
    restrict: "E",
    scope: {
    	startDate: "=",
    	endDate: "=",
    	releasedLast: "=",
    	applyDates: "="
    },
    controller: function($scope, $filter) {
    	$scope.visibilityStart = false;
    	$scope.visibilityEnd = false;
    	$scope.isTouched = false;
    	$scope.linkClick = function()
    	{
    		$scope.isTouched = !$scope.isTouched;
    	}
    	
    	$scope.isActive = function()
    	{    		
    		return ($scope.startDate !== '' || $scope.endDate !== '' ||  $scope.releasedLast !== '');
    	}   
    	$scope.dateApply = function()
    	{
    		$scope.applyDates = $scope.applyDates+1;
    		$scope.releasedLast = '';
    	}
    	$scope.today = function()
    	{
    		var date = new Date();
    		return $filter('date')(date, 'yyyy-MM-dd');
    	}
    	$scope.Delete = function(e) {
  		  $scope.$destroy();
  		}
    },
    templateUrl: '/templates/directives/filter-date.html',
    link: function(scope, element, attrs) {
    	$(document).bind('click', function(event){
            var isClickedElementChildOfPopup = element
                .find(event.target)
                .length > 0;

            if (isClickedElementChildOfPopup)
                return;

            scope.$apply(function(){
            	scope.isTouched = false;
            	scope.visibilityStart = false;
            	scope.visibilityEnd = false;
            });
        });
    	scope.resetDate = function(){
    	  scope.startDate = '';
    	  scope.endDate = '';
    	  scope.releasedLast = '';
    	  scope.applyDates = 0;
    	}
    	scope.setDate = function(type){
    		scope.releasedLast = type;
    		scope.startDate = '';
      	  	scope.endDate = '';
      	  	scope.applyDates = 0;
    	}    	
        scope.$on('$destroy', function () {
        	element.remove();
        	scope.Delete();
        });
    }
  };
});