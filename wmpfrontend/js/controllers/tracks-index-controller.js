angular.module('WhoPlayMusic').factory( 'Tracks', function($resource, $rootScope){
	if($rootScope.globals.currentUser){
		return $resource('http://api.djdownload.me/tracks?token='+$rootScope.globals.currentUser.token);
	}
	return $resource('http://api.djdownload.me/tracks');
});

angular.module('WhoPlayMusic').controller('TracksIndexController', function($scope, $http, Tracks, $filter, $routeParams, $location, $rootScope, $cookies, $window, $httpParamSerializer) {
  $scope.itemsPerPage = 50;
  $scope.currentPage = 1;
  $scope.maxSize = 3;
  $scope.predicate = '';
  $scope.reverse = true;
  $scope.sortBy = 'release-desc';
  $scope.tracks = [];//Track.query({page: $scope.currentPage, limit: $scope.itemsPerPage});
  $scope.totalItems = 0;
  var orderBy = $filter('orderBy');
  var body = angular.element(document).find('body');
  $scope.pages = [];
  $scope.totalPages = 0;
  $scope.activeType = 0;
  $scope.activeGenre = 0;
  $scope.activeLabel = 0;
  $scope.selectedArtists = [];
  $scope.filter = [];
  $scope.artists = [];
  $scope.types = [];
  $scope.labels = [];
  $scope.genres = [];
  $scope.startDate = '';
  $scope.endDate = '';
  $scope.releasedLast = '';
  $scope.onlyWav = 'off';
  $scope.applyDates = 0;
  $scope.queryParams = {};
  $scope.getStart = false;
  $scope.init = false;

  if($routeParams.artists !== undefined){
	  $scope.selectedArtists = $routeParams.artists.split(',');
  }
  if($routeParams.label !== undefined){
	  $scope.activeLabel = parseInt($routeParams.label);
  }
  if($routeParams.genre !== undefined){
	  $scope.activeGenre = parseInt($routeParams.genre);
  }
  if($routeParams.last !== undefined){
	  $scope.releasedLast = $routeParams.last;
  }
  if($routeParams.start !== undefined && $routeParams.end !== undefined){
	  $scope.startDate = $routeParams.start;
	  $scope.endDate = $routeParams.end;
	  $scope.applyDates = 1;
  }
  if($routeParams.limit !== undefined){
	  $scope.itemsPerPage = ($routeParams.limit > 150)?150:$routeParams.limit;
  }
  if($routeParams.page !== undefined){
	  $scope.currentPage = $routeParams.page;
  }
  if($routeParams.sort !== undefined){
	  $scope.sortBy = $routeParams.sort;
  }
  if($routeParams.type !== undefined){
	  $scope.activeType = $routeParams.type;
  }
  if($routeParams.wav !== undefined){
	  $scope.onlyWav = $routeParams.wav;
  }

  $scope.query = function(page, limit){
	  var search = $location.search();
	  var searchOld = $location.search();

		 if(page===undefined){
			 page = $scope.currentPage;
			 search.page = page;
			 /*if(page >= 1){
			 }else if(page == 1 && $routeParams.page !== undefined && $routeParams.page != 1){
				 search.page = page;
			 }*/
	     }
		 if(limit===undefined){
			 limit = $scope.itemsPerPage;
			 search.limit = limit;
			 /*if(limit != 50){
				 search.limit = limit;
			 }else if(limit == 50 && $routeParams.limit !== undefined && $routeParams.limit != 50){
				 search.limit = limit;
			 }	*/ 
		 }
		 if($scope.sortBy != 'release-desc'){
			 search.sort = $scope.sortBy;
		 }
		 var query = {page: page, limit: limit, sort: $scope.sortBy};
		 if($scope.activeType > 0){
			 query.type = $scope.activeType;
			 if(!search.type) search.type = $scope.activeType;
		 }else{
			 if(search.type) delete search.type;
		 }
		 if($scope.activeGenre > 0){
			 query.genre = $scope.activeGenre;
			 if(!search.genre) search.genre = $scope.activeGenre;
		 }else{
			 if(search.genre) delete search.genre;
		 }
		 if($scope.selectedArtists.length > 0){
			 query.artists = $scope.selectedArtists.join(',');
			 search.artists = query.artists;
		 }else{
			 if(search.artists) delete search.artists;
		 }
		 if($scope.activeLabel > 0){
			 query.label = $scope.activeLabel;
			 if(!search.label) search.label = $scope.activeLabel;
		 }else{
			 if(search.label) delete search.label;
		 }

		 if($scope.releasedLast !== ''){
			 query.last = $scope.releasedLast;
			 if(!search.last || search.last != query.last) search.last = $scope.releasedLast;
		 }else{
			 delete search.last;
		 }
		 if($scope.applyDates > 0){
			 query.start = $scope.startDate;
			 query.end = $scope.endDate;
			 search.start = query.start;
			 search.end = query.end;
		 }else{
			 delete search.start;
			 delete search.end;
		 }

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
			 
		 return query;
  }

  $scope.getTracks = function(page, limit){
	  if($scope.getStart == true)
		  return;
	 $scope.queryParams = $scope.query(page, limit);
	 if(page===undefined){
		 page = $scope.currentPage;
     }
	 if(limit===undefined){
		 limit = $scope.itemsPerPage;
	 }
	 body.addClass('waiting');
	 $scope.getStrart = true;
	 Tracks.get($scope.queryParams, function(response){
		 $scope.tracks = response.tracks;
		 $scope.totalItems = response.total;
		 $scope.currentPage = response.page;
		 $scope.artists = response.artists;
		 $scope.types = response.types;
		 $scope.labels = response.labels;
		 $scope.genres = response.genres;
		 $scope.limit = response.limit;
		 $scope.totalPages = calculateTotalPages();
		 if(page > $scope.totalPages){
			 page = $scope.totalPages;
		 }
		 if (page > 0 && page <= $scope.totalPages) {
			  $scope.pages = getPages(page, $scope.totalPages, $scope.maxSize);
		 }
		 body.removeClass('waiting');
		 $scope.getStart = false;
	 })
  }

  $scope.downloadArchive = function() {
	  if($rootScope.globals.currentUser === undefined){
			$location.path('/payment-page');
			return;
		}
		$rootScope.isLoading = true;
		$http.get('http://api.djdownload.me/download-tracks/?token='+$rootScope.globals.currentUser.token +'&'+ $httpParamSerializer($scope.queryParams)).then(function(response){
	    		$rootScope.isLoading = false;
				if(!response.data.success){
					$location.path('/payment-page');
				}else{
					$scope.quoteSub = response.data.quoteSub;
					$scope.quote = response.data.quote;
					$window.location = 'http://api.djdownload.me/download-tracks-stream/?token='+$rootScope.globals.currentUser.token +'&'+ $httpParamSerializer($scope.queryParams);
					$scope.downloaded = true;
				if($scope.quoteSub && $scope.quote.length > 0){
					$rootScope.globals.currentUser.quotes.quotePromo = $scope.quote.quotePromo;
					$rootScope.globals.currentUser.quotes.quoteExclusive = $scope.quote.quoteExclusive;

					$cookies.putObject('globals', $rootScope.globals);
					$rootScope.quotes = $scope.quote;
				}
				}
			})
	};

  $scope.order = function(predicate, reverse) {
		$scope.predicate = predicate;
		if($scope.predicate == predicate){
			$scope.reverse = !reverse;
		}else{
			$scope.reverse = false;
		}

		$scope.sortBy = predicate+'-'+($scope.reverse?'desc':'asc');
  };

  $scope.resetAll = function(){
	  $scope.activeType = 0;
	  $scope.activeGenre = 0;
	  $scope.activeLabel = 0;
	  $scope.selectedArtists = [];
	  $scope.startDate = '';
	  $scope.endDate = '';
	  $scope.releasedLast = '';
	  $scope.applyDates = 0;
	  $location.search('');
  }

//init
  if($rootScope.siteModeShow()){
	  $scope.getTracks();
	  $scope.init = true;
  }
  //end init

  var siteModeListener = function(newValue, oldValue, scope){
	  if (newValue === oldValue) { return;};
	  if($rootScope.siteModeShow()){
		  $scope.getTracks();
	  }
  }
  $rootScope.$watch('parseSiteMode',siteModeListener);

  var listenerFilterHandler = function (newValue, oldValue, scope) {
    if (newValue === oldValue) { return;};
    $scope.getTracks();
  };

  $scope.$watchGroup(['activeGenre','activeType','activeLabel','selectedArtists','sortBy','currentPage','itemsPerPage', 'applyDates','releasedLast','onlyWav'], listenerFilterHandler);

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
