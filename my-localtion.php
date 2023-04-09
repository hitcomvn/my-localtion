<?php
/**
 * Plugin Name: My Location
 * Plugin URI: https://github.com/hitcomvn/my-localtion
 * Description: A simple plugin to log user's location using HTML5 geolocation and Google Maps API.
 * Version: 1.0
 * Author: hitcomvn
 * Author URI: https://github.com/hitcomvn/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue the script for HTML5 geolocation.
add_action( 'wp_enqueue_scripts', 'my_location_enqueue_scripts' );
function my_location_enqueue_scripts() {
    wp_enqueue_script( 'my-location-geolocation', plugins_url( 'js/my-location-geolocation.js', __FILE__ ), array( 'jquery' ), '1.0', true );
}

// Add the log page in the WordPress admin menu.
add_action( 'admin_menu', 'my_location_create_admin_menu' );
function my_location_create_admin_menu() {
    add_menu_page(
        'My Location Log',
        'My Location Log',
        'manage_options',
        'my_location_log',
        'my_location_display_log'
    );
}

// Display the log page content.
function my_location_display_log() {
    $log_file = plugin_dir_path( __FILE__ ) . 'my_location.log';
    if ( file_exists( $log_file ) ) {
        echo '<h1>My Location Log</h1>';
        echo '<table>';
        echo '<tr><th>Date and Time</th><th>Latitude</th><th>Longitude</th><th>Address</th></tr>';
        $lines = file( $log_file );
        foreach ( $lines as $line ) {
            $parts = explode( ',', $line );
            echo '<tr>';
            echo '<td>' . $parts[0] . '</td>';
            echo '<td>' . $parts[1] . '</td>';
            echo '<td>' . $parts[2] . '</td>';
            echo '<td>' . $parts[3] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No location log found.</p>';
    }
}

// Save the user's location to the log file.
function my_location_save_to_log( $latitude, $longitude, $address ) {
    $log_file = plugin_dir_path( __FILE__ ) . 'my_location.log';
    $current_time = current_time( 'mysql' );
    $data = $current_time . ',' . $latitude . ',' . $longitude . ',' . $address . "\n";
    file_put_contents( $log_file, $data, FILE_APPEND | LOCK_EX );
}

// Handle the AJAX request to get the user's location.
add_action( 'wp_ajax_my_location_get_location', 'my_location_get_location' );
add_action( 'wp_ajax_nopriv_my_location_get_location', 'my_location_get_location' );

function my_location_get_location() {
    $location = array(
        'latitude' => '',
        'longitude' => '',
        'address' => ''
    );

    // Lấy thông tin vị trí của người dùng từ HTML5 Geolocation
    if (isset($_COOKIE['my_location_lat']) && isset($_COOKIE['my_location_lng'])) {
        $location['latitude'] = $_COOKIE['my_location_lat'];
        $location['longitude'] = $_COOKIE['my_location_lng'];
    }

    // Lấy thông tin vị trí của người dùng từ API của Google Maps
    if (empty($location['latitude']) || empty($location['longitude'])) {
        $location_info = my_location_get_location_info();
        if ($location_info) {
            $location['latitude'] = $location_info['latitude'];
            $location['longitude'] = $location_info['longitude'];
            $location['address'] = $location_info['address'];
        }
    }

    return $location;
}
function my_location_save_to_log( $data ) {
    $log_file = plugin_dir_path( __FILE__ ) . 'log.txt';
    $data = date( 'Y-m-d H:i:s' ) . ' - ' . $data . "\n";
    file_put_contents( $log_file, $data, FILE_APPEND | LOCK_EX );
}

function my_location_update_user_location( $user_id ) {
    if ( isset( $_POST['my_location_latitude'] ) && isset( $_POST['my_location_longitude'] ) ) {
        $latitude = sanitize_text_field( $_POST['my_location_latitude'] );
        $longitude = sanitize_text_field( $_POST['my_location_longitude'] );
        $address = my_location_get_address_by_coordinates( $latitude, $longitude );
        update_user_meta( $user_id, 'my_location_latitude', $latitude );
        update_user_meta( $user_id, 'my_location_longitude', $longitude );
        update_user_meta( $user_id, 'my_location_address', $address );
        my_location_save_to_log( 'User ' . $user_id . ' updated location: (' . $latitude . ', ' . $longitude . ')' );
    }
}

function my_location_save_user_location_meta_box( $user_id ) {
    if ( current_user_can( 'edit_user', $user_id ) ) {
        my_location_update_user_location( $user_id );
    }
}

add_action( 'admin_init', 'my_location_init' );
add_action( 'wp_enqueue_scripts', 'my_location_enqueue_scripts' );
add_action( 'wp_ajax_my_location_update_user_location', 'my_location_ajax_update_user_location' );
add_action( 'wp_ajax_nopriv_my_location_update_user_location', 'my_location_ajax_update_user_location' );
add_action( 'show_user_profile', 'my_location_user_location_meta_box' );
add_action( 'edit_user_profile', 'my_location_user_location_meta_box' );
add_action( 'personal_options_update', 'my_location_save_user_location_meta_box' );
add_action( 'edit_user_profile_update', 'my_location_save_user_location_meta_box' );
