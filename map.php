<?php
/*
Plugin Name: My Location Plugin
Plugin URI: https://example.com
Description: A plugin to show user location and save it to log file.
Version: 1.0
Author: Your Name
Author URI: https://example.com
*/

// Register plugin activation hook
register_activation_hook(__FILE__, 'my_location_plugin_activate');

// Function to activate plugin
function my_location_plugin_activate() {
    // Create log file
    $file = fopen(plugin_dir_path(__FILE__) . 'log.txt', 'w');
    fclose($file);
}

// Register shortcode to display location button
add_shortcode('location_button', 'my_location_button');

// Function to display location button
function my_location_button() {
    // Check if user location cookie exists
    if (!isset($_COOKIE['user_location'])) {
        // Display location button
        return '<button onclick="getLocation()">Đồng ý chia sẻ vị trí</button>';
    }
}

// Add JavaScript to get user location and save to log file
add_action('wp_footer', 'my_location_script');

// Function to add JavaScript
function my_location_script() {
    // Check if user location cookie exists
    if (!isset($_COOKIE['user_location'])) {
        // Add JavaScript
        echo '
        <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            var url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" + lat + "," + lng + "&key=AIzaSyBs5CTk8t1VvTKyTYZ7dIwyd4WetqW7jLc";

            // Send request to Google Maps API
            fetch(url)
            .then(response => response.json())
            .then(data => {
                // Save user location to log file
                var address = data.results[0].formatted_address;
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "' . plugin_dir_url(__FILE__) . 'save_location.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.send("location=" + address);
            })
            .catch(error => {
                alert("Error: " + error);
            });
        }
        </script>
        ';
    }
}
