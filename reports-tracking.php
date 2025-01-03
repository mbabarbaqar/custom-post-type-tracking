<?php
/**
 * @package Cultures_User_Tracking
 * @version 1.0
 */
/*
Plugin Name: Cultures User Tracking
Plugin URI: 
Description: This plugin is for local use. It contains user tracking data.
Author: Muhammad babar
Version: 1.0
Author URI: 
*/
defined('ABSPATH') or die("Cannot access pages directly.");
define('HS_ROOT_PATH', plugin_dir_path(__FILE__));

require_once(HS_ROOT_PATH . 'includes/user-tracking.php' );
require_once(HS_ROOT_PATH . 'includes/cron_functions.php' );
//require_once( plugin_dir_path( __FILE__ ) . 'includes/user-tracking-to-export.php' );
//require_once( plugin_dir_path( __FILE__ ) . 'export.php' );

//to install table for tracking
function installer(){
    include('installer/installer.php');
}
register_activation_hook( __file__, 'installer' );
?>
