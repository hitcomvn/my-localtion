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

function get_location() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var lat = position.coords.latitude;
      var lng = position.coords.longitude;
      var location = {
        lat: lat,
        lng: lng
      };
      save_location(location);
    });
  }
}

