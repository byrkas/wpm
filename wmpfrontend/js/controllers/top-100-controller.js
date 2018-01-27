angular.module('WhoPlayMusic').factory( 'Top', function($resource, $rootScope){
	if($rootScope.globals){
		return $resource('http://api.wpm.zeit.style/top?token='+$rootScope.globals.currentUser.token);
	}
	return $resource('http://api.wpm.zeit.style/top');
});

angular.module('WhoPlayMusic').controller('TopController', function($scope, $http, $filter, Top, $routeParams, $rootScope, $location, $window, $httpParamSerializer, $cookies) {
  $scope.predicate = '';
  $scope.reverse = true;
  $scope.sortBy = 'release-desc';
  $scope.tracks = [];
  var orderBy = $filter('orderBy');
  var body = angular.element(document).find('body');
  $scope.activeType = 0;
  $scope.activeGenre = 0;
  $scope.activeLabel = 0;
  $scope.selectedArtists = [];
  $scope.filter = [];
  $scope.artists = [];
  $scope.types = [];
  $scope.labels = [];
  $scope.genres = [];
  $scope.queryParams = {};

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

  $scope.query = function(){
	 var search = $location.search();
	 if($scope.sortBy != 'release-desc'){
		 search.sort = $scope.sortBy;
	 }

	 var query = {sort: $scope.sortBy};
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
		 if(!search.last) search.last = $scope.releasedLast;
	 }else{
		 if(search.last) delete search.last;
	 }
	 if($scope.onlyWav == 'on'){
		 query.wav = 1;
	 }
	 if($scope.applyDates == true){
		 query.start = $scope.startDate;
		 query.end = $scope.endDate;
	 }
	 if($rootScope.globals.currentUser){
		 query.showPromo = $rootScope.globals.currentUser.quotes.showPromo;
	 }

	 $location.search(search);
	 $scope.queryParams = query;

	 return query;
  }

  $scope.getTracks = function(){
	 var query = $scope.query();

	 body.addClass('waiting');
	 Top.get(query, function(response){
			 $scope.tracks = response.tracks;
			 $scope.artists = response.artists;
			 $scope.types = response.types;
			 $scope.labels = response.labels;
			 $scope.genres = response.genres;
			 body.removeClass('waiting');
		})
  }

  $scope.downloadArchive = function() {		
		if($rootScope.globals.currentUser === undefined){
			$location.path('/payment-page');
			return;
		}
		$rootScope.isLoading = true;
		$http.get('http://api.wpm.zeit.style/download-top/?token='+$rootScope.globals.currentUser.token +'&'+ $httpParamSerializer($scope.queryParams)).then(function(response){
	    		$rootScope.isLoading = false;
				if(!response.data.success){
					$location.path('/payment-page');
				}else{
					$scope.quoteSub = response.data.quoteSub;
					$scope.quote = response.data.quote;
					$window.location = 'http://api.wpm.zeit.style/download-top-stream/?token='+$rootScope.globals.currentUser.token +'&'+ $httpParamSerializer($scope.queryParams);
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
  }

  //init
  if($rootScope.siteModeShow()){
	  $scope.getTracks();
  }	 
  //end init

  var siteModeListener = function(newValue, oldValue, scope){
	  if($rootScope.siteModeShow()){
		  $scope.getTracks();
	  }
  }
  $rootScope.$watch('parseSiteMode',siteModeListener);
  
  var listenerFilterHandler = function (newValue, oldValue, scope) {
    if (newValue === oldValue) { return;};
    $scope.getTracks();
  };

  $scope.$watchGroup(['activeGenre','activeType','activeLabel','selectedArtists','sortBy'], listenerFilterHandler);

});
