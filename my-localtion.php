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

function my_location_save_to_log( $location ) {
    $log_path = plugin_dir_path( __FILE__ ) . 'my-location.log';

    $log_data = '[' . date( 'Y-m-d H:i:s' ) . ']';
    $log_data .= ' IP Address: ' . $_SERVER['REMOTE_ADDR'];
    $log_data .= ' Latitude: ' . $location['latitude'];
    $log_data .= ' Longitude: ' . $location['longitude'];
    $log_data .= ' Address: ' . $location['address'];
    $log_data .= "\n";

    file_put_contents( $log_path, $log_data, FILE_APPEND | LOCK_EX );
}

function my_location_init
