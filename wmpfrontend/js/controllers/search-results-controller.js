angular.module('WhoPlayMusic').factory( 'Search', function($resource){  
  return $resource('http://api.djdownload.me/search-results/:query');
});

angular.module('WhoPlayMusic').controller('SearchResultsController', function($scope, $http, Search, $filter, $routeParams, $window, ngMeta, $rootScope, $location) {
	$scope.tracks = [];
	$scope.url = $window.location.href;
	$scope.itemsPerPage = 50;
  $scope.currentPage = 1;
  $scope.maxSize = 3;
  $scope.predicate = '';
  $scope.reverse = true;
  $scope.sortBy = 'release-desc';
  $scope.tracks = [];
  $scope.totalItems = 0;
  var orderBy = $filter('orderBy');
  var body = angular.element(document).find('body');
  $scope.pages = [];
  $scope.totalPages = 0;  
  $scope.filter = [];
  $scope.onlyWav = 'off';
  $rootScope.search = $routeParams.query;
  
  if($routeParams.limit !== undefined){
	  $scope.itemsPerPage = ($routeParams.limit > 150)?150:$routeParams.limit;
  }
  if($routeParams.page !== undefined){
	  $scope.currentPage = $routeParams.page;
  }
  if($routeParams.sort !== undefined){
	  $scope.sortBy = $routeParams.sort;
  }
  if($routeParams.wav !== undefined){
	  $scope.onlyWav = $routeParams.wav;
  }
	
  $scope.getTracks = function(page, limit){
	  var search = $location.search();
	 if(page===undefined){
		 page = $scope.currentPage;
		 if (page != 1 || $routeParams.page !== undefined){
			 search.page = page;
		 }	
     }
	 if(limit===undefined){
		 limit = $scope.itemsPerPage;
		 if (limit != 50 || $routeParams.limit !== undefined){
			 search.limit = limit;
		 }
	 }
	 if($scope.sortBy != 'release-desc'){
		 search.sort = $scope.sortBy;
	 }
	 var query = {query: $routeParams.query, page: page, limit: limit, sort: $scope.sortBy};
	 
	 if($scope.onlyWav == 'on'){
		 query.wav = 1;
		 if(!search.wav) search.wav = $scope.onlyWav;
	 }else{
		 if(search.wav) delete search.wav;
	 }
	 if($rootScope.globals.currentUser){
		 query.showPromo = $rootScope.globals.currentUser.quotes.showPromo;
	 }
	 $location.search(search);
	 
	 body.addClass('waiting');
	 Search.get(query, function(response){
		 $scope.tracks = response.tracks;
		 $scope.totalItems = response.total;
		 $scope.currentPage = response.page;
		 $scope.limit = response.limit;
		 $scope.totalPages = calculateTotalPages();
		 if(page > $scope.totalPages){
			 page = $scope.totalPages;
		 }
		 if (page > 0 && page <= $scope.totalPages) {
			  $scope.pages = getPages(page, $scope.totalPages, $scope.maxSize);
		 }
		 body.removeClass('waiting');
		 
		 ngMeta.setTitle('Search results: '+ $routeParams.query);
		 ngMeta.setDefaultTag('og:title', 'Search results: '+ $routeParams.query);
		 ngMeta.setDefaultTag('twitter:title', 'Search results: '+ $routeParams.query);
		 //ngMeta.setDefaultTag('og:image', $scope.album.cover);
		// ngMeta.setDefaultTag('twitter:image', $scope.album.cover);
		 ngMeta.setDefaultTag('og:url', $scope.url);
		 ngMeta.setDefaultTag('og:description', 'Download Now on Who Play Music.');
		 ngMeta.setDefaultTag('twitter:description', 'Download Now on Who Play Music.');
	 })			
  }
  
  $scope.order = function(predicate, reverse) {
		$scope.predicate = predicate;
		if($scope.predicate == predicate){
			$scope.reverse = !reverse;
		}else{
			$scope.reverse = false;
		}
		
		$scope.sortBy = predicate+'-'+($scope.reverse?'desc':'asc');
  };
  
  //init
  $scope.getTracks();
  //end init
  
  var listenerFilterHandler = function (newValue, oldValue, scope) {
    if (newValue === oldValue) { return;};
    $scope.getTracks();
  };
  
  $scope.$watchGroup(['sortBy','currentPage','itemsPerPage','onlyWav'], listenerFilterHandler);
 
  function calculateTotalPages(){
      var totalPages = $scope.itemsPerPage < 1 ? 1 : Math.ceil($scope.totalItems / $scope.itemsPerPage);
      return Math.max(totalPages || 0, 1);
  };
  
  function makePage(number, text, isActive) {
    return {
      number: number,
      text: text,
      active: isActive
    };
  }
  
  function getPages(currentPage, totalPages, maxSize) {
	    var pages = [];
	    var boundaryLinkNumbers = true;
	    
	    // Default page limits
	    var startPage = 1, endPage = totalPages;
	    var isMaxSized = maxSize < totalPages;

	    // recompute if maxSize
	    if (isMaxSized) {	      
	        // Visible pages are paginated with maxSize
	        startPage = (Math.ceil(currentPage / maxSize) - 1) * maxSize + 1;

	        // Adjust last page if limit is exceeded
	        endPage = Math.min(startPage + maxSize - 1, totalPages);
	    }

	    // Add page number links
	    for (var number = startPage; number <= endPage; number++) {
	      var page = makePage(number, number, number === currentPage);
	      pages.push(page);
	    }

	    // Add links to move between page sets
	    if (isMaxSized && maxSize > 0) {
	      if (startPage > 1) {
	        if (!boundaryLinkNumbers || startPage > 3) { //need ellipsis for all options unless range is too close to beginning
	        var previousPageSet = makePage(startPage - 1, '...', false);
	        pages.unshift(previousPageSet);
	      }
	        if (boundaryLinkNumbers) {
	          if (startPage === 3) { //need to replace ellipsis when the buttons would be sequential
	            var secondPageLink = makePage(2, '2', false);
	            pages.unshift(secondPageLink);
	          }
	          //add the first page
	          var firstPageLink = makePage(1, '1', false);
	          pages.unshift(firstPageLink);
	        }
	      }

	      if (endPage < totalPages) {
	        if (!boundaryLinkNumbers || endPage < totalPages - 2) { //need ellipsis for all options unless range is too close to end
	        var nextPageSet = makePage(endPage + 1, '...', false);
	        pages.push(nextPageSet);
	      }
	        if (boundaryLinkNumbers) {
	          if (endPage === totalPages - 2) { //need to replace ellipsis when the buttons would be sequential
	            var secondToLastPageLink = makePage(totalPages - 1, totalPages - 1, false);
	            pages.push(secondToLastPageLink);
	          }
	          //add the last page
	          var lastPageLink = makePage(totalPages, totalPages, false);
	          pages.push(lastPageLink);
	        }
	      }
	    }
	    return pages;
	  }  
	
});
