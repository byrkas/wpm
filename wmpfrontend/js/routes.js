angular.module('WhoPlayMusic').config(
		[ '$routeProvider', "$locationProvider", "$httpProvider", '$rootScopeProvider','vcRecaptchaServiceProvider','ngMetaProvider','$cookiesProvider',
				function($routeProvider, $locationProvider, $httpProvider, $rootScopeProvider, vcRecaptchaServiceProvider, ngMetaProvider,$cookiesProvider) {
					$locationProvider.html5Mode(true);
					$rootScopeProvider.isMaintain = false;
					//$cookiesProvider.defaults.secure = true;

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
						controller : 'TracksIndexController as TI',
						//reloadOnSearch: false,
					    data: {
					        meta: {
					          'title': 'Tracks'
					        }
					      }
					}).when('/track/:id', {
						templateUrl : 'templates/pages/track.html',
						controller : 'TrackViewController as TV'
					}).when('/album/:id', {
						templateUrl : 'templates/pages/album.html',
						controller : 'AlbumIndexController as AI'
					}).when('/search/:query', {
						templateUrl : 'templates/pages/search-results.html',
						controller : 'SearchResultsController as SR'
					}).when('/account/login', {
						templateUrl : 'templates/pages/user-login.html',
						controller : 'UserLoginController as UL',
					    data: {
					        meta: {
					          'title': 'Sign in'
					        }
					      }
					}).when('/account/signup', {
						templateUrl : 'templates/pages/user-signup.html',
						controller : 'UserSignupController as US',
					    data: {
					        meta: {
					          'title': 'Sign up'
					        }
					      }
					}).when('/account/forgot-password', {
						templateUrl : 'templates/pages/user-forgot-password.html',
						controller : 'UserForgotController as UF',
					    data: {
					        meta: {
					          'title': 'Forgot password'
					        }
					      }
					}).when('/account/profile', {
						templateUrl : 'templates/pages/user-profile.html',
						controller : 'UserProfileController as UP',
					    data: {
					        meta: {
					          'title': 'Account Settings'
					        }
					      }
					}).when('/account/logout', {
						templateUrl : 'templates/pages/user-login.html',
						controller : function() {
							AuthenticationService.ClearCredentials();
							$locationProvider.path('/account/login');
						}
					}).when('/account/downloads', {
						templateUrl : 'templates/pages/my-downloads.html',
						controller : 'MyDownloadsController as MD',
					    data: {
					        meta: {
					          'title': 'My Downloads'
					        }
					      }
					}).when('/account/favorite', {
						templateUrl : 'templates/pages/my-favorites.html',
						controller : 'MyFavoritesController as MF',
					    data: {
					        meta: {
					          'title': 'My Favorites'
					        }
					      }
					}).when('/payment-page', {
						templateUrl : 'templates/pages/payment-page.html',
						controller : 'PaymentPageController as PP'
					}).when('/top100', {
						templateUrl : 'templates/pages/top100.html',
						controller : 'TopController as T',
					    data: {
					        meta: {
					          'title': 'TOP 100 Tracks'
					        }
					      }
					}).when('/maintain', {
						templateUrl : 'templates/pages/maintain.html',
					}).when('/page/:slug', {
						templateUrl : 'templates/pages/page.html',
						controller : 'PageController as P'
					}).when('/404', {
						templateUrl : 'templates/pages/error.html',
						controller : 'ErrorPageController as EP'
					}).otherwise({
						redirectTo : '/404'
					});
				} ])
		.run(
				[
						'$rootScope','$location','$cookies','$injector','$http','$route','ngMeta','$sce',
						function($rootScope, $location, $cookies, $injector, $http, $route, ngMeta, $sce) {
							ngMeta.init();
							$rootScope.keyboardModalShow = false;
							$rootScope.mobileMenu = false;
							$rootScope.cursorStyle = {};
							$rootScope.siteMode = 0;
							$rootScope.parseSiteMode = false;
							$rootScope.footer = $cookies.getObject('footer') || '';
							$rootScope.isLoading = false;
							$rootScope.globals = $cookies.getObject('globals') || {};
							$rootScope.quotes = ($rootScope.globals.currentUser !== undefined)?$rootScope.globals.currentUser.quotes || {}:{};

							$rootScope.$on("$routeChangeStart", function (event, next, current) {
								//if(current !== undefined && next.templateUrl != current.templateUrl)
									var maintainPath = 'http://api.wpm.zeit.style/is-maintain/';
									if($rootScope.isLogged()){
										maintainPath = maintainPath + '?token='+$rootScope.globals.currentUser.token;
									}
									$http.get(maintainPath).then(function(response){
										$rootScope.parseSiteMode = true;
										if(response.data.logout == 1){
											$rootScope.globals = {};
								            $cookies.remove('globals');
											$location.path('/account/login');
										}
										if(response.data.isMaintain === 1){
											$rootScope.isMaintain = 1;
											$location.path('/maintain');
										}
										$rootScope.siteMode = response.data.siteMode;
										$rootScope.footer = $sce.trustAsHtml(response.data.footer);
	
										if ($rootScope.globals.currentUser) {
											if(response.data.quotes){
												$rootScope.globals.currentUser.quotes = response.data.quotes;
												$cookies.putObject('globals', $rootScope.globals);
												$rootScope.quotes = response.data.quotes;
											}
										}else{
											var routesToRedirect = ['/account/downloads','/account/favorite','/account/profile'];
											if($rootScope.siteMode == 0){
												routesToRedirect.push('/tracks');
												routesToRedirect.push('/top100');
												routesToRedirect.push('/search/:query');
												routesToRedirect.push('/track/:id');
												routesToRedirect.push('/album/:id');
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
								$cookies.remove('globals');
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
								return ($cookies.getObject('currentPlaying') == id);
							}
							$rootScope.isDownloaded = function(id)
							{
								var downloaded = $cookies.getObject('downloaded') || [];
								return (downloaded.indexOf(id) > -1 );
							}

							$rootScope.playedList = function(id)
							{
								var played = $cookies.getObject('played') || [];
								return (played.indexOf(id) > -1 );
							}

							$rootScope.$on('rootScope:toggleKeyboardModal', function (event, data) {
								$rootScope.toggleKeyboardModal();
							});
						} ]);
;