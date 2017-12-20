angular.module('WhoPlayMusic')
.directive('filterArtist', function() {
  return {
    replace: true,
    restrict: "E",
    scope: {
      artists: "=",
      selectedArtists: "=",
    },
    controller: function($scope) {
    	$scope.isTouched = false;
    	$scope.linkClick = function()
    	{
    		$scope.isTouched = !$scope.isTouched;
    	}
    	$scope.isActive = function()
    	{    		
    		return ($scope.selectedArtists.length !== 0);
    	} 
    	$scope.Delete = function(e) {
  		  $scope.$destroy();
  		}
    },
    templateUrl: '/templates/directives/filter-artist.html',
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
    	
    	scope.resetArtist = function(){
    		scope.selectedArtists = [];
        }
    	scope.selectArtists = function(){
    		var selectedArtists = [];
    		angular.forEach(scope.artists, function(artist, key) {
                if(artist.checked) {
                	selectedArtists.push(artist.id);
                }
            })
            scope.selectedArtists = selectedArtists;
    	}
        scope.$on('$destroy', function () {
        	element.remove();
        	scope.Delete();
        });
    }
  };
});