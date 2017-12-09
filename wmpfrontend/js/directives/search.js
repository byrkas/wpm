angular.module('WhoPlayMusic')
.directive('search', function() {
  return {
    restrict: "A",
    scope: {
    },    
    controller: function($scope, $http, $rootScope, $location, $window){ 
    	 $scope.inputWidth = '200';
    	 $scope.toggleAutoSearch = 'none';
    	 $scope.search = '';
    	 $scope.searchData = null;

    	 $scope.initiateAutoSearch = function() {
    		 if($scope.search.length >= 3){
    	    	 $scope.toggleAutoSearch = 'visible';
    	    	 $http.get('http://api.wpm.zeit.style/search?query=' + $scope.search).then(function(response){
    	    		 $scope.searchData = response.data;
    	    		 $scope.toggleAutoSearch = 'block';
    			})
    		 }
    	 }

    	 $scope.selectedSearchResult = function(input) {
	    	 $scope.search = input;	
	    	 $scope.toggleAutoSearch = 'none';
    	 }
    },
    templateUrl: '/templates/directives/search.html',
    link: function(scope, element, attrs) {
    	$(document).bind('click', function(event){
            var isClickedElementChildOfPopup = element
                .find(event.target)
                .length > 0;

            if (isClickedElementChildOfPopup)
                return;

            scope.$apply(function(){
            	scope.toggleAutoSearch = 'none';
            });
        });
    }
  };
});