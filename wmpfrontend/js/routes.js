angular.module('WhoPlayMusic').config(
		[ '$routeProvider', "$locationProvider", "$httpProvider", '$rootScopeProvider','vcRecaptchaServiceProvider','ngMetaProvider',
				function($routeProvider, $locationProvider, $httpProvider, $rootScopeProvider, vcRecaptchaServiceProvider, ngMetaProvider) {
					$locationProvider.html5Mode(true);
					$rootScopeProvider.isMaintain = false;
					
					ngMetaProvider.useTitleSuffix(true);
				    ngMetaProvider.setDefaultTitle('Who Play Music');
				    ngMetaProvider.setDefaultTitleSuffix(' | Who Play Music');
				    ngMetaProvider.setDefaultTag('description', 'Download and listen to new, exclusive, electronic dance music and house tracks. Available on mp3 and wav at the world’s largest store for DJs.');
				    
					vcRecaptchaServiceProvider.setDefaults({
					    key: '6LdC0jIUAAAAABX9nREg28dzpXF902M8DfqtXtoP',
					    theme: 'dark',
					    size: 'normal',
					    type: 'image',
					    lang: 'en'
					  });

					// $httpProvider.defaults.useXDomain = true;

					$routeProvider.when('/', {
						// redirect to the notes index
						redirectTo : '/tracks'
					}).when('/tracks', {
						templateUrl : 'templates/pages/tracks.html',
						controller : 'TracksIndexController', 
						//reloadOnSearch: false,
					    data: {
					        meta: {
					          'title': 'Tracks'
					        }
					      }
					}).when('/track/:id', {
						templateUrl : 'templates/pages/track.html',
						controller : 'TrackViewController'
					}).when('/album/:id', {
						templateUrl : 'templates/pages/album.html',
						controller : 'AlbumIndexController'
					}).when('/search/:query', {
						templateUrl : 'templates/pages/search-results.html',
						controller : 'SearchResultsController'
					}).when('/account/login', {
						templateUrl : 'templates/pages/user-login.html',
						controller : 'UserLoginController',
					    data: {
					        meta: {
					          'title': 'Sign in'
					        }
					      }
					}).when('/account/signup', {
						templateUrl : 'templates/pages/user-signup.html',
						controller : 'UserSignupController',
					    data: {
					        meta: {
					          'title': 'Sign up'
					        }
					      }
					}).when('/account/forgot-password', {
						templateUrl : 'templates/pages/user-forgot-password.html',
						controller : 'UserForgotController',
					    data: {
					        meta: {
					          'title': 'Forgot password'
					        }
					      }
					}).when('/account/profile', {
						templateUrl : 'templates/pages/user-profile.html',
						controller : 'UserProfileController',
					    data: {
					        meta: {
					          'title': 'Account Settings'
					        }
					      }
					}).when('/account/logout', {
						templateUrl : 'templates/pages/user-login.html',
						controller : function() {
							AuthenticationService.ClearCredentials();
						}
					}).when('/account/downloads', {
						templateUrl : 'templates/pages/my-downloads.html',
						controller : 'MyDownloadsController',
					    data: {
					        meta: {
					          'title': 'My Downloads'
					        }
					      }
					}).when('/account/favorite', {
						templateUrl : 'templates/pages/my-favorites.html',
						controller : 'MyFavoritesController',
					    data: {
					        meta: {
					          'title': 'My Favorites'
					        }
					      }
					}).when('/payment-page', {
						templateUrl : 'templates/pages/payment-page.html',
						controller : 'PaymentPageController'
					}).when('/top100', {
						templateUrl : 'templates/pages/top100.html',
						controller : 'TopController',
					    data: {
					        meta: {
					          'title': 'TOP 100 Tracks'
					        }
					      }
					}).when('/maintain', {
						templateUrl : 'templates/pages/maintain.html',
					}).when('/page/:slug', {
						templateUrl : 'templates/pages/page.html',
						controller : 'PageController'
					}).when('/404', {
						templateUrl : 'templates/pages/error.html',
						controller : 'ErrorPageController'
					}).otherwise({
						redirectTo : '/404'
					});
				} ])
		.run(
				[
						'$rootScope','$location','$cookieStore','$injector','$http','$route','ngMeta','$sce',
						function($rootScope, $location, $cookieStore, $injector, $http, $route, ngMeta, $sce) {
							ngMeta.init();
							$rootScope.keyboardModalShow = false;
							$rootScope.mobileMenu = false;
							$rootScope.cursorStyle = {};
							$rootScope.footer = $cookieStore.get('footer') || '';
							$rootScope.isLoading = false;
							$rootScope.globals = $cookieStore.get('globals') || {};
							$rootScope.quotes = $rootScope.globals.currentUser.quotes || {};
							
							$rootScope.$on("$routeChangeStart", function (event, next, current) {
								$http.get('http://api.wpm.zeit.style/is-maintain/').then(function(response){
									if(response.data.isMaintain === 1){
										$rootScope.isMaintain = 1;
										$location.path('/maintain');
									}
									$rootScope.siteMode = response.data.siteMode;
									$rootScope.footer = $sce.trustAsHtml(response.data.footer);
									
									if ($rootScope.globals.currentUser) {
									}else{
										var routesToRedirect = ['/account/downloads','/account/favorite','/account/profile'];
										if($rootScope.siteMode == 0){
											routesToRedirect.push('/tracks');
											routesToRedirect.push('/track/:id');
										}
										var route = next.$$route.originalPath;
										if(routesToRedirect.indexOf(route) !== -1) {								
											$location.path('/account/login');
										}
									}									
								})
							});									
							
							$injector.get("$http").defaults.transformRequest =	 function(data, headersGetter) { 
								 if($rootScope.globals.currentUser) {
									 headersGetter()['Authorization'] = 'Bearer ' + $rootScope.globals.currentUser.authdata; } 
								 if(data) { return angular.toJson(data); } 
							};							 

							$rootScope.logout = function() {
								$rootScope.globals = {};
								$cookieStore.remove('globals');
							}
							$rootScope.isLogged = function() {
								return ($rootScope.globals.currentUser);
							}
							$rootScope.siteModeShow = function(){
								if($rootScope.siteMode == 1)
									return true;
								return ($rootScope.isLogged());
							}
							$rootScope.toggleKeyboardModal = function(){
								$rootScope.keyboardModalShow = !$rootScope.keyboardModalShow;
							}
							$rootScope.getKeyboardModalShow = function(){
								return $rootScope.keyboardModalShow;
							}
							$rootScope.setKeyboardModalShow = function(param){
								$rootScope.keyboardModalShow = param;
							}
							
							$rootScope.isCurrentPlaying = function(id)
							{
								return ($cookieStore.get('currentPlaying') == id);
							}
							$rootScope.isDownloaded = function(id)
							{
								var downloaded = $cookieStore.get('downloaded') || [];
								return (downloaded.indexOf(id) > -1 );
							}
							
							$rootScope.playedList = function(id)
							{
								var played = $cookieStore.get('played') || [];
								return (played.indexOf(id) > -1 );
							}
							
							$rootScope.$on('rootScope:toggleKeyboardModal', function (event, data) {
								$rootScope.toggleKeyboardModal();
							});
						} ]);
;