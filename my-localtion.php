<?php
/**
 * Plugin Name: My Location
 * Plugin URI: https://www.example.com/my-location
 * Description: A simple plugin to get and display user's location on the website.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://www.example.com
 */

/**
 * Define constants
 */
define( 'MY_LOCATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_LOCATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include required files
 */
require_once MY_LOCATION_PLUGIN_DIR . 'public-functions.php';

/**
 * Register activation hook
 */
register_activation_hook( __FILE__, 'my_location_activate' );

/**
 * Register deactivation hook
 */
register_deactivation_hook( __FILE__, 'my_location_deactivate' );

/**
 * Register uninstall hook
 */
register_uninstall_hook( __FILE__, 'my_location_uninstall' );

/**
 * Run activation function
 */
function my_location_activate() {
    my_location_create_tables();
}

/**
 * Run deactivation function
 */
function my_location_deactivate() {
    // No action needed
}

/**
 * Run uninstall function
 */
function my_location_uninstall() {
    my_location_drop_tables();
}

/**
 * Enqueue scripts and styles on frontend
 */
function my_location_enqueue_scripts() {
    wp_enqueue_script( 'my-location', MY_LOCATION_PLUGIN_URL . 'js/my-location.js', array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'my_location_enqueue_scripts' );

/**
 * Display user's location on the website
 *
 * @param boolean $use_geo_html_5 Whether or not to use geo HTML 5.
 */
function my_location_display_user_location( $use_geo_html_5 = true ) {
    $latitude = '';
    $longitude = '';
    $address = '';
    $source = '';

    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $latitude = get_user_meta( $user_id, 'my_location_latitude', true );
        $longitude = get_user_meta( $user_id, 'my_location_longitude', true );
        $address = get_user_meta( $user_id, 'my_location_address', true );
        $source = 'User\'s account';
    } elseif ( $use_geo_html_5 && ! empty( $_COOKIE['my_location_latitude'] ) && ! empty( $_COOKIE['my_location_longitude'] ) ) {
        $latitude = $_COOKIE['my_location_latitude'];
        $longitude = $_COOKIE['my_location_longitude'];
        $address = my_location_get_address_by_lat_long( $latitude, $longitude );
        $source = 'HTML 5 Geolocation';
    } else {
        $ip = my_location_get_ip_address();
        $location = my_location_get_location_by_ip( $ip );
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $address = $location['address'];
        $source = 'IP Address';
    }

    // Output location information
    echo '<div class="my-location">';
    echo '<h3>Your Location</h3>';
    echo '<ul>';
    echo '<li><strong>Latitude:</strong> ' . $latitude . '</li>';
    echo '<li><strong>Longitude:</strong> ' . $longitude . '</li>';
    echo '<li><strong>Address:</strong> ' . $address . '</li>';
    echo '<li><strong>Source:</strong> ' . $source . '</li>';
    echo '</ul>';
    echo '</div>';
}

/**
 * AJAX callback
