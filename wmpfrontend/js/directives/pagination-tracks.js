angular.module('WhoPlayMusic')
.directive('paginationTracks', function(Track) {
  return {
    replace: true,
    restrict: "E",
    scope: {
      onlyWav: "=",
      itemsPerPage: "=",
      currentPage: "=",
      totalItems: "=",
      pages: '=',      
      totalPages: '='
    },
    controller: function($scope) {
    },
    templateUrl: '/templates/directives/pagination-tracks.html',
    link: function(scope, element, attrs) {      
		  scope.setItemsPerPage = function(limit){
			  scope.itemsPerPage = limit;
		  }
		  
		  scope.selectPage = function(page){
			  if(scope.currentPage != page){
				  scope.currentPage = page;
			  }
		  }
		  
		  scope.toggleOnlyWav = function(){
			  if(scope.onlyWav == 'on')
				  scope.onlyWav = 'off';
			  else
				  scope.onlyWav = 'on';
		  }
     }
  };
});