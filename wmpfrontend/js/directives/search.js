angular.module('WhoPlayMusic')
.directive('search', ['$location' ,function(location) {
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
    	 $scope.clicked = false;

    	 $scope.initiateAutoSearch = function() {
    		 if($scope.search.length >= 3 && $scope.clicked == false){
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
	    	 $scope.toggleAutoSearch = 'none';
    	 }
     	$scope.Delete = function(e) {
  		  $scope.$destroy();
  		}
     	$scope.hideSearch = function(){
     		$scope.toggleAutoSearch = 'none';
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
    		scope.clicked = false;
            if(event.which === 13) {
            	if(liSelected !== undefined && liSelected.hasClass('tt-link')){
            		location.url(liSelected.attr('ng-href'));
            		scope.$apply(function(){
                    	scope.toggleAutoSearch = 'none';
                    });
            	}else{
            		scope.$apply(function(){
            			scope.toggleAutoSearch = 'none';
                    	scope.startSearch();
                    });
            	}

                event.preventDefault();
                scope.clicked = true;
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
        scope.$on('$destroy', function () {
          	element.remove();
          	scope.Delete();
          });
    }
  };
}]);