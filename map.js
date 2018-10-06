var currentBubble = null;
	
function createMarker(map, latitude, longitude, clubUrlName, clubName) {
	var marker = new google.maps.Marker({
		position: new google.maps.LatLng(latitude, longitude),
		title: clubName
	});
	var bubble = new google.maps.InfoWindow({
		content: '<a href="' + clubUrlName + '">' + htmlEncode(clubName) + '</a>'
	});
	marker.addListener('click', function() {
		if (currentBubble != bubble) {
			if (currentBubble) currentBubble.close();
			bubble.open(map, this);
			currentBubble = bubble;
		}
	});
	marker.setMap(map);
}

function htmlEncode(text) {
	return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
