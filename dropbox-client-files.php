<?php
/**
 * Plugin Name: Dropbox Client Files
 * Plugin URI:
 * Description: This plugin allows you to browse files, download files, and upload files to a sub-folder in Dropbox.
 * Version:1.0
 * Author: Alok Shrestha
 * Text Domain: dcf
 * License: GPLv3 or later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'admin/class-fflguard-dropbox.php';

/**
 * The core plugin class that is used to define internationalization and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'public/class-fflguard-public.php';



/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fflguard_dropbox() {

    $plugin = new Fflguard_Dropbox();

}
run_fflguard_dropbox();

function public_fflguard_dropbox() {

    $plugin = new Fflguard_Public();

}
public_fflguard_dropbox();
