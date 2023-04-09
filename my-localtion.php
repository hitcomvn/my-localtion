<?php
/*
Plugin Name: My Location
Description: A plugin to get user's location and save to log file
Version: 1.0
Author: Your Name
*/

function my_location_init() {
    if (is_admin()) {
        add_action('admin_menu', 'my_location_add_options_page');
        add_action('admin_init', 'my_location_register_settings');
    }
}
add_action('init', 'my_location_init');

function my_location_add_options_page() {
    add_options_page('My Location Settings', 'My Location', 'manage_options', 'my_location_settings', 'my_location_options_page');
}

function my_location_register_settings() {
    register_setting('my_location_settings_group', 'my_location_api_key');
}

function my_location_options_page() {
?>
    <div class="wrap">
        <h1>My Location Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('my_location_settings_group'); ?>
            <?php do_settings_sections('my_location_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Google Maps API Key:</th>
                    <td><input type="text" name="my_location_api_key" value="<?php echo esc_attr(get_option('my_location_api_key')); ?>" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

function my_location_update_user_location() {
    if (isset($_POST['my_location_update_user_location'])) {
        $latitude = $_POST['my_location_latitude'];
        $longitude = $_POST['my_location_longitude'];
        $address = $_POST['my_location_address'];
        $source = $_POST['my_location_source'];
        $timestamp = current_time('mysql');

        $log_data = "$timestamp | $latitude | $longitude | $address | $source\n";
        $log_file = plugin_dir_path(__FILE__) . 'my_location.log';

        $handle = fopen($log_file, 'a');
        fwrite($handle, $log_data);
        fclose($handle);
    }
}
add_action('wp_loaded', 'my_location_update_user_location');

function my_location_display_user_location($use_geo_html_5 = true) {
    if ($use_geo_html_5 && function_exists('geoip_detect2_get_info_from_current_ip')) {
        $location = geoip_detect2_get_info_from_current_ip();
        $latitude = $location->latitude;
        $longitude = $location->longitude;
        $address = $location->city . ', ' . $location->country->name;
        $source = 'GeoIP HTML5';
    } else {
        $api_key = get_option('my_location_api_key');

        if (empty($api_key)) {
            return '';
        }

        $latitude = '';
        $longitude = '';
        $address = '';
        $source = '';

        if (isset($_POST['my_location_update_user_location'])) {
            $latitude = $_POST['my_location_latitude'];
            $longitude = $_POST['my_location_longitude'];
            $address = $_POST['my_location_address'];
            $source =
