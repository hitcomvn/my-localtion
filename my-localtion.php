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
    add_action( 'wp_enqueue_scripts', 'my_location_enqueue_scripts' );
    add_action( 'wp_ajax_my_location_update_user_location', 'my_location_update_user_location' );
    add_action( 'wp_ajax_nopriv_my_location_update_user_location', 'my_location_update_user_location' );
}
add_action( 'init', 'my_location_init' );

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

function my_location_save_to_log( $location ) {
    $date = date( 'Y-m-d H:i:s' );
    $log = $date . " - " . $location . "\n";
    $log_file = plugin_dir_path( __FILE__ ) . 'my-location.log';
    file_put_contents( $log_file, $log, FILE_APPEND | LOCK_EX );
}

?>
