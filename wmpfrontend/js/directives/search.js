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
    	 $scope.autoSearchStarted = false;
    	 $scope.autoSearch = '';

    	 $scope.initiateAutoSearch = function() {
    		 if($scope.search.length >= 3){
    	    	 $scope.toggleAutoSearch = 'visible';
    	    	 if($scope.autoSearch != $scope.search){
    	    		 $scope.autoSearch = $scope.search;
    	    		 $http.get('http://api.wpm.zeit.style/search?query=' + $scope.search).then(function(response){
        	    		 $scope.searchData = response.data;
        	    		 $scope.toggleAutoSearch = 'block';
        			})
    	    	 }
	    		 $scope.toggleAutoSearch = 'block';
    		 }
    	 }

    	 $scope.selectedSearchResult = function(input) {
	    	 $scope.search = input;
	    	 $scope.toggleAutoSearch = 'none';
    	 }
    	 $scope.startSearch = function(){
    		 $location.path('/search/'+$scope.search);
    	 }
    },
    templateUrl: '/templates/directives/search.html',
    link: function(scope, element, attrs) {
    	var li = $('.tt-suggestion');
    	var liSelected;

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
    	element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
            	if(liSelected !== undefined && liSelected.hasClass('tt-link')){
            		 window.location = liSelected.attr('ng-href');
            	}else{
            		scope.$apply(function(){
                    	scope.toggleAutoSearch = 'none';
                    	scope.startSearch();
                    });
            	}

                event.preventDefault();
            }else if(event.which === 40){
                if(liSelected){
                    liSelected.removeClass('selected');
                    next = liSelected.next();
                    if(next.length > 0){
                        liSelected = next.addClass('selected');
                    }else{
                        liSelected = li.eq(0).addClass('selected');
                    }
                }else{
                    liSelected = li.eq(0).addClass('selected');
                }
            }else if(event.which === 38){
                if(liSelected){
                    liSelected.removeClass('selected');
                    next = liSelected.prev();
                    if(next.length > 0){
                        liSelected = next.addClass('selected');
                    }else{
                        liSelected = li.last().addClass('selected');
                    }
                }else{
                    liSelected = li.last().addClass('selected');
                }
            }
        });
    }
  };
});