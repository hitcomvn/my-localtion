<?php
/**
 * Plugin Name: My Location
 * Description: A simple plugin to get user location and save to log file.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 */

function my_location_get_user_location() {
    $latitude = '';
    $longitude = '';
    $address = '';

    // Try to get user's location using HTML5 geolocation API
    if ( isset( $_COOKIE['my_location_latitude'] ) && isset( $_COOKIE['my_location_longitude'] ) ) {
        // Use latitude and longitude from cookie if available
        $latitude = $_COOKIE['my_location_latitude'];
        $longitude = $_COOKIE['my_location_longitude'];
    } else {
        // Use HTML5 geolocation API to get user's location
        $geo_options = array(
            'timeout' => 10
        );
        $position = wp_remote_get( 'https://www.googleapis.com/geolocation/v1/geolocate?key=' . MY_LOCATION_GOOGLE_MAPS_API_KEY, array(
            'method'      => 'POST',
            'timeout'     => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers'     => array(),
            'body'        => '',
            'cookies'     => array()
        ) );

        if ( ! is_wp_error( $position ) && $position['response']['code'] == 200 ) {
            $location = json_decode( $position['body'] );
            $latitude = $location->location->lat;
            $longitude = $location->location->lng;
        }
    }

    // Use Google Maps API to get address from latitude and longitude
    if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
        $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . MY_LOCATION_GOOGLE_MAPS_API_KEY . '&latlng=' . $latitude . ',' . $longitude . '&sensor=false';

        $response = wp_remote_get( $geocode_url, array(
            'timeout' => 10
        ) );

        if ( ! is_wp_error( $response ) && $response['response']['code'] == 200 ) {
            $geocode = json_decode( $response['body'] );
            if ( $geocode->status == 'OK' ) {
                $address = $geocode->results[0]->formatted_address;
            }
        }
    }

    return array(
        'latitude' => $latitude,
        'longitude' => $longitude,
        'address' => $address
    );
}

function my_location_save_to_log1( $location ) {
    $log_path = plugin_dir_path( __FILE__ ) . 'my-location.log';

    $log_data = '[' . date( 'Y-m-d H:i:s' ) . ']';
    $log_data .= ' IP Address: ' . $_SERVER['REMOTE_ADDR'];
    $log_data .= ' Latitude: ' . $location['latitude'];
    $log_data .= ' Longitude: ' . $location['longitude'];
    $log_data .= ' Address: ' . $location['address'];
    $log_data .= "\n";

    file_put_contents( $log_path, $log_data, FILE_APPEND | LOCK_EX );
}

if ( !function_exists('my_location_save_to_log') ) {
    function my_location_save_to_log( $data ) {
        // Open the log file for writing
        $file = fopen( plugin_dir_path( __FILE__ ) . 'my-location.log', 'a' );

        // Write the data to the log file
        fwrite( $file, $data . "\n" );

        // Close the log file
        fclose( $file );
    }
}


/**
 * Save user location meta box data
 *
 * @param int $user_id The ID of the user being saved.
 */
function my_location_save_user_location_meta_box( $user_id ) {
    // Make sure the current user can edit the user and the nonce is valid.
    if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['my_location_nonce'] ) || ! wp_verify_nonce( $_POST['my_location_nonce'], 'my_location_save_user_location_meta_box' ) ) {
        return;
    }

    // Get the user's latitude, longitude, address, and source from the form data.
    $latitude  = isset( $_POST['my_location_latitude'] ) ? sanitize_text_field( $_POST['my_location_latitude'] ) : '';
    $longitude = isset( $_POST['my_location_longitude'] ) ? sanitize_text_field( $_POST['my_location_longitude'] ) : '';
    $address   = isset( $_POST['my_location_address'] ) ? sanitize_text_field( $_POST['my_location_address'] ) : '';
    $source    = isset( $_POST['my_location_source'] ) ? sanitize_text_field( $_POST['my_location_source'] ) : '';

    // Update the user's latitude, longitude, address, and source in the database.
    update_user_meta( $user_id, 'my_location_latitude', $latitude );
    update_user_meta( $user_id, 'my_location_longitude', $longitude );
    update_user_meta( $user_id, 'my_location_address', $address );
    update_user_meta( $user_id, 'my_location_source', $source );
}

/**
 * Display user's location on the website
 *
 * @param boolean $use_geo_html_5 Whether or not to use geo HTML 5.
 */
function my_location_display_user_location( $use_geo_html_5 = true ) {
    $latitude  = '';
    $longitude = '';
    $address   = '';
    $source    = '';

    if ( is_user_logged_in() ) {
        $user_id   = get_current_user_id();
        $latitude  = get_user_meta( $user_id, 'my_location_latitude', true );
        $longitude = get_user_meta( $user_id, 'my_location_longitude', true );
        $address   = get_user_meta( $user_id, 'my_location_address', true );
        $source    = get_user_meta( $user_id, 'my_location_source', true );
    }

    if ( empty( $latitude ) && empty( $longitude ) && $use_geo_html_5 ) {
        echo '<div id="my-location-message"></div>';
    }

    if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
        echo '<div id="my-location-map"></div>';
        echo '<div id="my-location-address">' . $address . '</div>';
        echo '<div id="my-location-source">' . $source . '</div>';
    }
}
// Register plugin configuration menu
function my_location_register_settings_page() {
    add_options_page('My Location Settings', 'My Location', 'manage_options', 'my-location', 'my_location_settings_page');
}
add_action('admin_menu', 'my_location_register_settings_page');
// Save user location information to log file
function my_location_save_to_log() {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $latitude = '';
    $longitude = '';
    $source = 'HTML5 Geolocation';

    // Try to get location using HTML5 Geolocation
    if (isset($_COOKIE['my_location_latitude']) && isset($_COOKIE['my_location_longitude'])) {
        $latitude = $_COOKIE['my_location_latitude'];
        $longitude = $_COOKIE['my_location_longitude'];
    } else {
        // If HTML5 Geolocation is not available, get location using Google Maps API
        $location = my_location_get_location_by_ip($ip_address);
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $source = 'Google Maps API';
    }

    $time = current_time('mysql');
    $data = "{$time}\t{$ip_address}\t{$latitude}\t{$longitude}\t{$source}\n";
    $log_file = plugin_dir_path(__FILE__) . 'log.txt';
    file_put_contents($log_file, $data, FILE_APPEND);
}
add_action('init', 'my_location_save_to_log');
// Display plugin configuration page
function my_location_settings_page() {
    ?>
    <div class="wrap">
        <h1>My Location Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('my_location_settings');
            do_settings_sections('my_location_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Update the user's location via AJAX
 */
function my_location_update_user_location() {
    // Get the user's latitude and longitude from the AJAX request.
    $latitude = isset( $_POST['latitude'] ) ? sanitize_text_field( $_POST['latitude'] ) : '';
    $longitude = isset( $_POST['longitude'] ) ? sanitize_text_field( $_POST['longitude'] ) : '';

    // Try to get the user's location using Google Maps API if HTML5 Geolocation is not available
    if ( empty( $latitude ) || empty( $longitude ) ) {
        $geolocation = my_location_get_user_location_by_google_maps_api();

        if ( ! is_wp_error( $geolocation ) ) {
            $latitude = $geolocation['latitude'];
            $longitude = $geolocation['longitude'];
        }
    }

    // Update the user's latitude and longitude in the database.
    if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'my_location_latitude', $latitude );
        update_user_meta( $user_id, 'my_location_longitude', $longitude );
    }

    // Return a success response.
    wp_send_json_success();
}

function my_location_init() {
    add_action( 'wp_enqueue_scripts', 'my_location_enqueue_scripts' );

    add_shortcode( 'my_location', 'my_location_display_user_location_shortcode' );

    add_action( 'admin_menu', 'my_location_add_admin_menu' );
    add_action( 'admin_init', 'my_location_settings_init' );

    // Try to get the user's location from Google Maps API if the API key is set.
    $api_key = get_option( 'my_location_api_key' );
    if ( ! empty( $api_key ) ) {
        add_action( 'wp_footer', 'my_location_get_user_location_google_maps', 20 );
    } else {
        add_action( 'wp_footer', 'my_location_get_user_location_html5', 20 );
    }

    // Log user location data.
    add_action( 'wp_footer', 'my_location_log_user_location', 30 );
}

