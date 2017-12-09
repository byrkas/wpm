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
    	$scope.isActive = function()
    	{    		
    		return ($scope.selectedArtists.length !== 0);
    	} 
    },
    templateUrl: '/templates/directives/filter-artist.html',
    link: function(scope, element, attrs) {
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
    }
  };
});