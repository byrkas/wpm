angular.module('WhoPlayMusic').factory('AuthenticationService',
    ['Base64', '$http', '$cookies', '$rootScope', '$timeout', '$window',
    function (Base64, $http, $cookies, $rootScope, $timeout, $window) {
        var service = {};

        service.Login = function (username, password, callback) {
            $http.post('http://api.djdownload.me/login', { username: username, password: password }, {headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }})
                .then(function (response) {
                    callback(response);
                },function(){delete $window.sessionStorage.token;});

        };        

        service.SetCredentials = function (username, password, token, quotes) {
            var authdata = Base64.encode(username + ':' + password);

            $rootScope.globals = {
                currentUser: {
                    username: username,
                    authdata: authdata,
                    token: token,
                    quotes: quotes
                }
            };
            $rootScope.quotes = quotes;

            //$http.defaults.headers.common.Authorization = 'Basic ' + authdata; // jshint ignore:line
            $cookies.putObject('globals', $rootScope.globals);
        };

        service.ClearCredentials = function () {
            $rootScope.globals = {};
            $cookies.remove('globals');
            //$http.defaults.headers.common.Authorization = '';
        };

        return service;
    }])
    .factory('Base64', function () {
    /* jshint ignore:start */

    var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

    return {
        encode: function (input) {
            var output = "";
            var chr1, chr2, chr3 = "";
            var enc1, enc2, enc3, enc4 = "";
            var i = 0;

            do {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);

                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;

                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }

                output = output +
                    keyStr.charAt(enc1) +
                    keyStr.charAt(enc2) +
                    keyStr.charAt(enc3) +
                    keyStr.charAt(enc4);
                chr1 = chr2 = chr3 = "";
                enc1 = enc2 = enc3 = enc4 = "";
            } while (i < input.length);

            return output;
        },

        decode: function (input) {
            var output = "";
            var chr1, chr2, chr3 = "";
            var enc1, enc2, enc3, enc4 = "";
            var i = 0;

            // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
            var base64test = /[^A-Za-z0-9\+\/\=]/g;
            if (base64test.exec(input)) {
                window.alert("There were invalid base64 characters in the input text.\n" +
                    "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
                    "Expect errors in decoding.");
            }
            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

            do {
                enc1 = keyStr.indexOf(input.charAt(i++));
                enc2 = keyStr.indexOf(input.charAt(i++));
                enc3 = keyStr.indexOf(input.charAt(i++));
                enc4 = keyStr.indexOf(input.charAt(i++));

                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;

                output = output + String.fromCharCode(chr1);

                if (enc3 != 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 != 64) {
                    output = output + String.fromCharCode(chr3);
                }

                chr1 = chr2 = chr3 = "";
                enc1 = enc2 = enc3 = enc4 = "";

            } while (i < input.length);

            return output;
        }
    };

    /* jshint ignore:end */
});

angular.module('WhoPlayMusic').controller('UserLoginController', 
	['$scope', '$rootScope', '$location', 'AuthenticationService', function ($scope, $rootScope, $location, AuthenticationService) {	
	$scope.login = function(){
		$scope.dataLoading = true;
		AuthenticationService.Login($scope.username, $scope.password, function(response) {
            if(response.data.success) {
                AuthenticationService.SetCredentials($scope.username, $scope.password, response.data.token, response.data.quotes);
                $location.path('/');
            } else {
                $scope.error = response.data.message;
                $scope.dataLoading = false;
            }
        });
	}
}]);

angular.module('WhoPlayMusic').controller('UserSignupController', 
	['$scope', '$rootScope', '$location' ,'$http', 'vcRecaptchaService', function ($scope, $rootScope, $location, $http, recaptcha) {
	$scope.key = '6LdC0jIUAAAAAI-Tq0q4SLShBBQsF8F8o08SqhnI';
	$scope.paymentOptions = ['PayPal','Credit / Debit Card','Webmoney / Bitcoin / Bank Transfer etc.'];
	
	$scope.signup = function(){
		$scope.dataLoading = true;
		
		$http.post('http://api.djdownload.me/signup', {captcha: $scope.captcha ,fullname: $scope.fullname, email: $scope.email, info: $scope.info, payment: $scope.payment}, {headers : {
            'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
        }})
        .then(function (response) {
        	$scope.dataLoading = false;
        	if(response.data.message != ''){
        		$rootScope.hasNotification = true;
        		$rootScope.notifMessage = response.data.message;
    			if(response.data.success){
    				$rootScope.notifType = 'success';
    			}else{
    				$rootScope.notifType = 'failure';
    			}
    		}
        },function(){
        	$scope.dataLoading = false;
        });
	}
}]);

angular.module('WhoPlayMusic').controller('UserForgotController', 
	['$scope', '$location','$http', '$rootScope', function ($scope, $location, $http, $rootScope) {
	
	$scope.forgot = function(){
		$scope.dataLoading = true;		
		$http.post('http://api.djdownload.me/forgot', {email: $scope.email}, {headers : {
            'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
        }})
        .then(function (response) {
        	$scope.dataLoading = false;
        	if(response.data.message != ''){
        		$rootScope.hasNotification = true;
        		$rootScope.notifMessage = response.data.message;
    			if(response.data.success){
    				$rootScope.notifType = 'success';
    			}else{
    				$rootScope.notifType = 'failure';
    			}
    		}
        },function(){
        	$scope.dataLoading = false;
        });
	}
}]);

angular.module('WhoPlayMusic').controller('UserProfileController', 
	['$scope', '$location','$http','$rootScope', function ($scope, $location, $http, $rootScope) {
	
	$scope.error = {};
	$scope.profile = function(){
		$scope.dataLoading = true;		
		if($scope.new_password != $scope.confirm_password){
			$scope.error.confirm_password = true;
		}else{
			$scope.error.confirm_password = null;
		}
		if($scope.error.confirm_passord !== true)
			$http.post('http://api.djdownload.me/profile', {old_password: $scope.old_password,new_password: $scope.new_password}, 
			{
				withCredentials: true,
				headers : {
					'Authorization':  'Bearer ' + $rootScope.globals.currentUser.token,
					'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
					}
				}
			)
	        .then(function (response) {
	        	$scope.dataLoading = false;
	        	if(response.data.message != ''){
	        		$rootScope.hasNotification = true;
	        		$rootScope.notifMessage = response.data.message;
	    			if(response.data.success){
	    				$rootScope.notifType = 'success';
	    			}else{
	    				$rootScope.notifType = 'failure';
	    			}
	    		}
	        },function(){
	        	$scope.dataLoading = false;
	        });
	}
}]);

