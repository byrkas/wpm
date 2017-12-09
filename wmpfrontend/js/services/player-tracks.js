angular.module('WhoPlayMusic').factory('PlayerTracks', function PlayerTracksFactory() {
  var playerTracks = [];
  return {
    getPlayerTracks: function() {
      return playerTracks;
    },
    addToPlayerTracks: function(track){
    	if (playerTracks.indexOf(track) == -1) {
    		playerTracks.push(track);
	    }
    }
  }
});
