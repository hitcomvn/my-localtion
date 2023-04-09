<?php
/*
Plugin Name: Location Logger
Description: Log user's location and IP address.
Version: 1.0
Author: Your Name
*/

// Add action to log user's location and IP address
add_action('wp_head', 'log_location');


function log_location() {
    // Get user's location using HTML5 Geo API
    if (isset($_GET['geo'])) {
        $latlng = explode(',', $_GET['geo']);
        $location = array(
            'lat' => $latlng[0],
            'lon' => $latlng[1]
        );
    } else {
        $location = array(
            'lat' => '',
            'lon' => ''
        );
    }
    
    // Get user's location using Google Maps API
    $apikey = 'AIzaSyBs5CTk8t1VvTKyTYZ7dIwyd4WetqW7jLc';
    if (!empty($apikey)) {
        if (isset($_POST['lat']) && isset($_POST['lng'])) {
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];

            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apikey}";
            $data = file_get_contents($url);
            $result = json_decode($data, true);

            if ($result['status'] == 'OK') {
                $address = $result['results'][0]['formatted_address'];
            } else {
                $address = '';
            }

            $location['lat'] = $lat;
            $location['lon'] = $lng;
            $location['address'] = $address;
        }
    }

    // Add log entry to files
    $log = date('Y-m-d H:i:s') . " - Latitude: {$location['lat']}, Longitude: {$location['lon']}\n";
    file_put_contents(plugin_dir_path(__FILE__) . 'location.log', $log, FILE_APPEND);

    $log2 = date('Y-m-d H:i:s') . " - Latitude: {$location['lat']}, Longitude: {$location['lon']}\n";
    file_put_contents(plugin_dir_path(__FILE__) . 'htm5gps.log', $log2, FILE_APPEND);

    $log3 = date('Y-m-d H:i:s') . " - Latitude: {$location['lat']}, Longitude: {$location['lon']}, Address: {$location['address']}\n";
    file_put_contents(plugin_dir_path(__FILE__) . 'apikey.log', $log3, FILE_APPEND);
}
function share_location() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var lat = position.coords.latitude;
      var lng = position.coords.longitude;

      // Send location data to server
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'your-server-url-here');
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify({
        lat: lat,
        lng: lng
      }));
    });
  }
}