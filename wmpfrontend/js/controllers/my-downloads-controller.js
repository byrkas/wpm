angular.module('WhoPlayMusic').factory( 'Downloads', function($resource, $rootScope){
  return $resource('http://api.wpm.zeit.style/downloads',null,{
		  'get' :{
			  withCredentials: true,
				headers : {
					'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
					}
		  }		
		});
});

angular.module('WhoPlayMusic').factory( 'DownloadArchive', function($resource, $rootScope){
	  return $resource('http://api.wpm.zeit.style/download-archive-stream',null,{
			  'get' :{
				  withCredentials: true,
					responseType: 'blob',
					headers : {
						'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
						}
			  }		
			});
	});

angular.module('WhoPlayMusic').controller('MyDownloadsController', function($scope, $http, $filter, Downloads, DownloadArchive, $routeParams, $rootScope, $location) { 	
  $scope.itemsPerPage = 50;
  $scope.currentPage = 1;
  $scope.maxSize = 3;
  $scope.predicate = '';
  $scope.reverse = true;
  $scope.sortBy = 'created-desc';
  $scope.tracks = [];
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
  $scope.applyDates = false;
  
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
	  $scope.applyDates = true;
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
		 if(page===undefined){
			 page = $scope.currentPage;
			 if(page > 1){
				 search.page = page;
			 }
	     }
		 if(limit===undefined){
			 limit = $scope.itemsPerPage;
			 if(limit != 50)
				 search.limit = limit;
		 }
		 if($scope.sortBy != 'created-desc'){
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
		 if($scope.applyDates == true){
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
	 var query = $scope.query(page, limit);
	 body.addClass('waiting');
	 $rootScope.isLoading = true;
	 Downloads.get(query, function(response){
			 $scope.tracks = response.tracks;
			 $scope.totalItems = response.total;
			 $scope.currentPage = response.page;
			 $scope.artists = response.artists;
			 $scope.types = response.types;
			 $scope.labels = response.labels;
			 $scope.genres = response.genres;
			 $scope.limit = response.limit;
			 $scope.totalPages = calculateTotalPages();
			 page = $scope.currentPage;
			 if(page > $scope.totalPages){
				 page = $scope.totalPages;
			 }
			 if (page > 0 && page <= $scope.totalPages) {				 
				  $scope.pages = getPages(page, $scope.totalPages, $scope.maxSize);
			 }
			 body.removeClass('waiting');
			 $rootScope.isLoading = false;
		})			
  }  
  
  $scope.downloadArchive = function(predicate, reverse) {
		 $rootScope.isLoading = true;
		 body.addClass('waiting');
		 var query = $scope.query();
		 
	  $http.get('http://api.wpm.zeit.style/download-archive-stream/', {
		  	params : query,
			withCredentials: true,
			responseType: 'blob',
			headers : {
				'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
				}
			}).then(function(response){
				$rootScope.isLoading = false;
				body.removeClass('waiting');
				
				var message = response.headers('X-CustomHeader');
 				if(message !== ''){
 					$rootScope.hasNotification = true;
	        		$rootScope.notifMessage = message;
	        		$rootScope.notifType = 'failure';
	        		 
 				}
				if(response.status == 200){
					var data = response.data;
	 				var filename = response.headers('X-filename');
	 				
	 		        var contentType = response.headers('content-type');	 		       
	 		        var linkElement = document.createElement('a');
	 		        try {
	 		            var blob = new Blob([data], { type: contentType });
	 		            var url = window.URL.createObjectURL(blob);
	 		 
	 		            linkElement.setAttribute('href', url);
	 		            linkElement.setAttribute("download", filename);
	 		 
	 		            var clickEvent = new MouseEvent("click", {
	 		                "view": window,
	 		                "bubbles": true,
	 		                "cancelable": false
	 		            });
	 		            linkElement.dispatchEvent(clickEvent);
	 		        } catch (ex) {
	 		            console.log(ex);
	 		        }
				}else{
					console.log(response);
				}
				 	
			});
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
  } 
  
  //init
  $scope.getTracks();
  //end init
  
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
