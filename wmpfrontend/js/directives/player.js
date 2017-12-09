angular.module('WhoPlayMusic')
.directive('player', ['$timeout','PlayerTracks', function($timeout, PlayerTracks) {
  return {
    replace: true,
    restrict: "E",
    scope: {
    },
    templateUrl: "templates/directives/player.html",
    link: function(scope, element) {    	
        $timeout(function(){        	
        	soundManager.setup({
                url: '/swf/',
                preferFlash: false
              });
        });

        scope.$on('$destroy', function(){
        });
        
        scope.playerTracks = PlayerTracks.getPlayerTracks();
    }
  };
}]);