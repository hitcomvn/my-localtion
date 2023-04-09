<?php
/*
Plugin Name: My Location Plugin
Plugin URI: https://example.com
Description: A plugin to require user location and log it.
Version: 1.0
Author: Your Name
Author URI: https://example.com
License: GPL2
*/

// Add action to run the function when WordPress initializes

function require_location() {
    if (isset($_COOKIE['user_location'])) {
        return;
    }

    // Check if user has shared their location
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $lat = $_POST['latitude'];
        $lng = $_POST['longitude'];
        setcookie('user_location', $lat . ',' . $lng, time() + 3600);
        return;
    }

    // Check if user's browser supports geolocation
    if (function_exists('geoip_detect2_get_info_from_current_ip')) {
        $user_location = geoip_detect2_get_info_from_current_ip();
        $lat = $user_location->location->latitude;
        $lng = $user_location->location->longitude;
        setcookie('user_location', $lat . ',' . $lng, time() + 3600);
        return;
    }

    // Show notification to share location
    echo '<p>Please share your location to access this website.</p>';
    exit();
}
add_action('init', 'require_location');

function has_shared_location() {
    // Check if user has shared location before
    if (isset($_COOKIE['shared_location'])) {
        return true;
    } else {
        return false;
    }
}


function get_html5_location() {
  $location = array();

  if (isset($_COOKIE['location'])) {
    $location = json_decode(stripslashes($_COOKIE['location']), true);
  } else {
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
      $location['latitude'] = sanitize_text_field($_POST['latitude']);
      $location['longitude'] = sanitize_text_field($_POST['longitude']);
      setcookie('location', json_encode($location), time() + 86400, '/');
    } else {
      // Geolocation API not supported or user denied access
      // Handle error
    }
  }

  return $location;
}
function get_apikey_location() {
  $api_key = 'AIzaSyBs5CTk8t1VvTKyTYZ7dIwyd4WetqW7jLc';
  $url = "https://www.googleapis.com/geolocation/v1/geolocate?key=" . $api_key;
  
  $response = wp_remote_post($url, array(
    'method' => 'POST',
    'headers' => array('Content-Type' => 'application/json'),
    'body' => ''
  ));

  if (is_wp_error($response)) {
    // handle error
  } else {
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body);
    
    if ($result->location) {
      $location = array(
        'latitude' => $result->location->lat,
        'longitude' => $result->location->lng
      );
      
      return $location;
    } else {
      // handle error
    }
  }
}



function save_location($location) {
    $log_path = plugin_dir_path(__FILE__) . 'logs/'; // Path to log folder
    $log_gps_file = 'loggps.txt'; // GPS log file
    $log_apikey_file = 'logapikey.txt'; // API key log file

    if ($location['method'] === 'gps') {
        $log_file = $log_path . $log_gps_file;
    } elseif ($location['method'] === 'apikey') {
        $log_file = $log_path . $log_apikey_file;
    } else {
        return;
    }

    // Open log file in append mode
    $fp = fopen($log_file, 'a');

    // Write location data to log file
    fwrite($fp, 'Time: ' . $location['time'] . "\n");
    fwrite($fp, 'Latitude: ' . $location['latitude'] . "\n");
    fwrite($fp, 'Longitude: ' . $location['longitude'] . "\n");
    fwrite($fp, 'Accuracy: ' . $location['accuracy'] . "\n");
    fwrite($fp, "\n");

    // Close log file
    fclose($fp);
}
function initMap() {
  // Request user location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        // Success, save location to loggps
        var location = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          timestamp: new Date(position.timestamp).toISOString()
        };
        save_location(location, "loggps.txt");
        
        // Show map with user location
        var map = new google.maps.Map(document.getElementById("map"), {
          zoom: 15,
          center: { lat: location.latitude, lng: location.longitude }
        });
        var marker = new google.maps.Marker({
          position: { lat: location.latitude, lng: location.longitude },
          map: map
        });
      },
      function() {
        // Error, could not get user location
        alert("Could not get your location. Please enable location services in your browser.");
      }
    );
  } else {
    // Error, geolocation not supported by browser
    alert("Your browser does not support location services.");
  }
}
