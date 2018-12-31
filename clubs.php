<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_club.php';
require_once 'private_php/p_html_functions.php';

pageHeader('Clubs', ['map.js']);

$clubs = Club::loadAll();
$jsMapMarkers = '';

$minLat = 90;
$maxLat = -90;
$minLong = 180;
$maxLong = -180;

foreach ($clubs as $club) {
	if ($club->hasMapCoordinates()) {
		$lat = $club->venueLatitude();
		$long = $club->venueLongitude();

		if ($lat < $minLat) $minLat = $lat;
		if ($lat > $maxLat) $maxLat = $lat;
		if ($long < $minLong) $minLong = $long;
		if ($long > $maxLong) $maxLong = $long;

		$jsMapMarkers .= "
			createMarker(map, $lat, $long, " . json_encode($club->urlName()) . ', '
				. json_encode($club->name()) . ');';
	}
}
?>

<div id="subNav">
	<?php echo clubNavBar(); ?>
</div>

<div id="subBody">
	<h2>Clubs</h2>

	<script type="text/javascript">
	document.write('<div id="map" style="width: 100%; height: 500px;"></div>');

	function clubMap() {
		var mapOptions = {
			center: new google.maps.LatLng(<?php echo ($minLat + $maxLat) / 2; ?>, <?php echo ($minLong + $maxLong) / 2; ?>),
			zoom: 10
		};
		var map = new google.maps.Map(document.getElementById('map'), mapOptions);
		var marker, bubble;
		<?php echo $jsMapMarkers; ?>
	}
	</script>

	<script id="mapScript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GoogleMapKey; ?>&amp;callback=clubMap"></script>

	<p>Please select a club to view.</p>
</div>

<?php
pageFooter();
?>