<?php
/*
Plugin Name: My Location
Plugin URI: https://example.com/
Description: A simple plugin to get user location and save it to log file
Version: 1.0
Author: Your Name
Author URI: https://example.com/
License: GPL2
*/

function my_location_init() {
    $user_id = get_current_user_id();

    // Only run this for non-logged in users or for logged in users who have not yet provided their location
    if (empty($user_id) || !get_user_meta($user_id, 'my_location_lat', true) || !get_user_meta($user_id, 'my_location_lng', true)) {
        ?>
        <script type="text/javascript">
            // Get the user's permission to access their location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Save the user's location in the log and in their user meta
                        var data = {
                            'action': 'my_location_save',
                            'lat': position.coords.latitude,
                            'lng': position.coords.longitude
                        };
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data);
                    },
                    function(error) {
                        console.log(error.message);
                    }
                );
            } else {
                console.log('Geolocation is not supported by this browser.');
            }
        </script>
        <?php
    }
}


function my_location_enqueue_scripts() {
    wp_enqueue_script( 'my-location-script', plugin_dir_url( __FILE__ ) . 'public-functions.js', array(), '1.0', true );
}

function my_location_update_user_location() {
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $location = my_location_get_location_by_api( $lat, $lng );
    my_location_save_to_log( $location );
    wp_send_json_success( $location );
}

function my_location_get_location_by_api( $lat, $lng ) {
    $api_key = 'AIzaSyBs5CTk8t1VvTKyTYZ7dIwyd4WetqW7jLc';
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&key=' . $api_key;
    $response = wp_remote_get( $url );
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( isset( $data['results'][0]['formatted_address'] ) ) {
        return $data['results'][0]['formatted_address'];
    } else {
        return 'Unknown';
    }
}


function my_location_save_to_log() {
    if (isset($_POST['lat']) && isset($_POST['lng'])) {
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];

        // Only log the location if the user has consented to share it
        if (get_user_meta(get_current_user_id(), 'my_location_consent', true) == 'true') {
            $log_data = array(
                'timestamp' => current_time('mysql'),
                'lat' => $lat,
                'lng' => $lng,
                'ip' => $_SERVER['REMOTE_ADDR']
            );

            $log_file = plugin_dir_path(__FILE__) . 'my-location.log';
            file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);
        }

        // Update the user meta with the new location
        update_user_meta(get_current_user_id(), 'my_location_lat', $lat);
        update_user_meta(get_current_user_id(), 'my_location_lng', $lng);
    }

    wp_die();
}
function my_location_watch_position() {
    ?>
    <script>
    var watchID;
    var options = {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
    };

    function success(position) {
        var lat  = position.coords.latitude;
        var lng = position.coords.longitude;

        // Only update the location if the user has consented to share it
        if (get_user_meta(get_current_user_id(), 'my_location_consent', true) == 'true') {
            var log_data = {
                timestamp: new Date(),
                lat: lat,
                lng: lng,
                ip: '<?php echo $_SERVER['REMOTE_ADDR']; ?>'
            };

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            xhr.send('action=my_location_save_to_log&log_data=' + JSON.stringify(log_data));
        }

        // Update the user meta with the new location
        update_user_meta(get_current_user_id(), 'my_location_lat', lat);
        update_user_meta(get_current_user_id(), 'my_location_lng', lng);
    };

    function error(err) {
        console.warn('ERROR(' + err.code + '): ' + err.message);
    };

    watchID = navigator.geolocation.watchPosition(success, error, options);
    </script>
    <?php
}
add_action('wp_footer', 'my_location_watch_position');

?>
