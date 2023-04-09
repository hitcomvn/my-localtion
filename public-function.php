<?php

/**
 * Get user's IP address
 *
 * @return string The IP address
 */
function my_location_get_ip_address() {
    if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    return $ip_address;
}

/**
 * Get user's location by IP address using IPInfo.io API
 *
 * @param string $ip_address The IP address to get location for
 *
 * @return array|bool The location data if found, false otherwise
 */
function my_location_get_location_by_ip( $ip_address ) {
    $url = 'https://ipinfo.io/' . $ip_address . '/json';
    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $location_data = json_decode( $body, true );

    if ( isset( $location_data['error'] ) ) {
        return false;
    }

    return $location_data;
}

/**
 * Get user's location by latitude and longitude using Google Maps API
 *
 * @param float $latitude The latitude
 * @param float $longitude The longitude
 *
 * @return array|bool The location data if found, false otherwise
 */
function my_location_get_location_by_lat_lng( $latitude, $longitude ) {
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $latitude . ',' . $longitude . '&key=' . MY_LOCATION_GOOGLE_MAPS_API_KEY;
    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $location_data = json_decode( $body, true );

    if ( ! isset( $location_data['results'] ) || empty( $location_data['results'] ) ) {
        return false;
    }

    return $location_data['results'][0];
}

/**
 * Save user's location to the database
 *
 * @param int $user_id The ID of the user
 * @param array $location_data The location data to save
 */
function my_location_save_user_location( $user_id, $location_data ) {
    update_user_meta( $user_id, 'my_location_latitude', $location_data['latitude'] );
    update_user_meta( $user_id, 'my_location_longitude', $location_data['longitude'] );
    update_user_meta( $user_id, 'my_location_address', $location_data['address'] );
    update_user_meta( $user_id, 'my_location_source', $location_data['source'] );
}

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

    if
