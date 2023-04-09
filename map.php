<?php
/*
Plugin Name: My Location
Description: Get user location using HTML5 Geolocation and Google Maps API
Version: 1.0
*/

// Require user to share location to access website
function require_location() {
  if(!isset($_COOKIE['user_location'])) { // Check if user location cookie exists
    setcookie('user_location', 'required', time() + (86400 * 30), '/'); // Set user location cookie for 30 days
    echo '<script>alert("Please share your location to access this website.");</script>'; // Alert user to share location
  }
}
add_action('wp_head', 'require_location');

// Get user location using HTML5 Geolocation
function get_html5_location() {
  if(isset($_COOKIE['user_location']) && $_COOKIE['user_location'] == 'allowed') { // Check if user allowed to share location
    echo '<script>
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
          var location = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          };
          console.log("User location (HTML5): ", location);
          save_location(location, "gps");
        });
      }
    </script>';
  }
}

// Get user location using Google Maps API
function get_apikey_location() {
  if(isset($_COOKIE['user_location']) && $_COOKIE['user_location'] == 'allowed') { // Check if user allowed to share location
    $api_key = 'AIzaSyBs5CTk8t1VvTKyTYZ7dIwyd4WetqW7jLc'; // Replace with your Google Maps API key
    echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&callback=initMap" async defer></script>
    <script>
      function initMap() {
        var geocoder = new google.maps.Geocoder();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function(position) {
            var location = {
              latitude: position.coords.latitude,
              longitude: position.coords.longitude
            };
            geocoder.geocode({"location": location}, function(results, status) {
              if (status === "OK") {
                console.log("User location (API): ", results[0].geometry.location);
                save_location(results[0].geometry.location, "apikey");
              } else {
                console.log("Geocode failed: " + status);
              }
            });
          });
        }
      }
    </script>';
  }
}

// Function to save location data to file
function save_location($location, $type) {
  $log_path = plugin_dir_path(__FILE__) . 'logs/'; // Path to log folder
  $log_gps_file = 'loggps.txt'; // GPS log file
  $log_apikey_file = 'logapikey.txt'; // API key log file

  // Check if log folder exists, create it if it doesn't
  if(!file_exists($log_path)) {
    mkdir($log_path);
  }

  // Save location data to appropriate log file
  if($type == "gps") {
    file_put_contents($log_path . $log_gps_file, date('Y-m-d H:i:s') . ' ' . $location['latitude'] . ',' . $location['longitude'] . PHP_EOL, FILE_APPEND);
  } else {
    file_put_contents($log_path . $log_apikey_file, date('Y-m-d H:i:s') . ' ' . $location->lat() . ',' . $location->lng() . PHP_EOL, FILE_APPEND);
  }
}

//
