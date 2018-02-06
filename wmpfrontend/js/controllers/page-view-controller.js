angular.module('WhoPlayMusic').factory( 'Page', function($resource){  
  return $resource('http://api.djdownload.me/page/:slug');
});

angular.module('WhoPlayMusic').controller('PaymentPageController', function($scope, $http, $rootScope, ngMeta, $sce) {
	$scope.page = {};
	
	$scope.getPage = function()
	{
		if($rootScope.globals.currentUser)
			$http.get('http://api.djdownload.me/payment-page/', {
				withCredentials: true,
				headers : {
					'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
					}
				}).then(function(response){
					$scope.page = response.data.page;
					ngMeta.setTitle($scope.page.title);
					 $scope.page.content = $sce.trustAsHtml($scope.page.content);
				})
		else
			$http.get('http://api.djdownload.me/payment-page/').then(function(response){
					$scope.page = response.data.page;
					ngMeta.setTitle($scope.page.title);
					 $scope.page.content = $sce.trustAsHtml($scope.page.content);
				})
	}
	
	$scope.getPage();
});

angular.module('WhoPlayMusic').controller('PageController', function($scope, $http, $routeParams,Page, ngMeta, $sce) {
	$scope.page = {};	
	Page.get({slug: $routeParams.slug}, function(response){
		 $scope.page = response.page;
		 ngMeta.setTitle($scope.page.title);
		 $scope.page.content = $sce.trustAsHtml($scope.page.content);
	 })	
});

angular.module('WhoPlayMusic').controller('ErrorPageController', function($scope, $http, $routeParams,Page, ngMeta, $sce) {
	ngMeta.setTitle('Oh No! 404 — Not Found');
});
