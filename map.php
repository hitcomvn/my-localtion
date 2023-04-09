<?php
/*
Plugin Name: Map Location
Description: Get user location using HTML5 Geolocation and Google Maps API
Version: 1.0
*/

// Require user to share location to access website

function require_location() {
  if(!isset($_COOKIE['user_location'])) { // Check if user location cookie exists
    setcookie('user_location', 'allowed', time() + (86400 * 30), '/'); // Set user location cookie for 30 days
    echo '<script>
            if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(function(position) {
                console.log("User location (GEO): ", position.coords.latitude, position.coords.longitude);
                save_location({
                  latitude: position.coords.latitude,
                  longitude: position.coords.longitude
                }, "gps");
              });
            }
            else {
              alert("Trình duyệt của bạn không hỗ trợ chia sẻ vị trí.");
            }
          </script>';
  }
}
add_action('wp_head', 'require_location');
// Update user location cookie to "allowed"

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




function save_location($location, $type) {
  $log_file = 'location_log.txt'; // Set log file name
  $file = fopen($log_file, 'a'); // Open log file in append mode
  $time = date('Y-m-d H:i:s'); // Get current time
  $data = $time . ',' . $type . ',' . $location['latitude'] . ',' . $location['longitude'] . "\n"; // Format data to be logged
  fwrite($file, $data); // Write data to log file
  fclose($file); // Close log file
}

if(isset($_COOKIE['user_location']) && $_COOKIE['user_location'] == 'allowed') { // Check if user allowed to share location
  if(isset($_GET['apikey'])) { // Check if API key location is set
    get_apikey_location(); // Get user location using API key
  } else {
    get_geolocation(); // Get user location using HTML5 geolocation
  }
}

