<?php
/**
 * Admin Functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add user location meta box to user profile page
 */
function my_location_add_user_location_meta_box() {
    add_meta_box( 'my_location_user_location', __( 'User Location', 'my-location' ), 'my_location_render_user_location_meta_box', 'user', 'normal', 'high' );
}

add_action( 'admin_init', 'my_location_add_user_location_meta_box' );

/**
 * Render user location meta box
 *
 * @param WP_User $user The user being edited.
 */
function my_location_render_user_location_meta_box( $user ) {
    wp_nonce_field( 'my_location_user_location', 'my_location_user_location_nonce' );

    $latitude = get_user_meta( $user->ID, 'my_location_latitude', true );
    $longitude = get_user_meta( $user->ID, 'my_location_longitude', true );
    $address = get_user_meta( $user->ID, 'my_location_address', true );
    $accuracy = get_user_meta( $user->ID, 'my_location_accuracy', true );
    $timestamp = get_user_meta( $user->ID, 'my_location_timestamp', true );
    ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th><label for="my_location_latitude"><?php esc_html_e( 'Latitude', 'my-location' ); ?></label></th>
                <td><input type="text" id="my_location_latitude" name="my_location_latitude" value="<?php echo esc_attr( $latitude ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="my_location_longitude"><?php esc_html_e( 'Longitude', 'my-location' ); ?></label></th>
                <td><input type="text" id="my_location_longitude" name="my_location_longitude" value="<?php echo esc_attr( $longitude ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="my_location_address"><?php esc_html_e( 'Address', 'my-location' ); ?></label></th>
                <td><input type="text" id="my_location_address" name="my_location_address" value="<?php echo esc_attr( $address ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="my_location_accuracy"><?php esc_html_e( 'Accuracy', 'my-location' ); ?></label></th>
                <td><input type="text" id="my_location_accuracy" name="my_location_accuracy" value="<?php echo esc_attr( $accuracy ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="my_location_timestamp"><?php esc_html_e( 'Timestamp', 'my-location' ); ?></label></th>
                <td><input type="text" id="my_location_timestamp" name="my_location_timestamp" value="<?php echo esc_attr( $timestamp ); ?>" /></td>
            </tr>
        </tbody>
    </table>

    <?php
}

/**
 * Save user location meta box data
 *
 * @param int $user_id The ID of the user being saved.
 */
function my_location_save_user_location_meta_box( $user_id ) {
    // Check if the nonce is valid.
    if ( ! isset( $_POST['my_location_user_location_nonce'] ) || ! wp_verify_nonce( $_POST['my_location_user_location_nonce'], 'my_location_user_location' ) ) {
        return;
    }

    //
