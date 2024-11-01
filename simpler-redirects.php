<?php
/**
 * @package Simpler_Redirects
 */
/*
 * Plugin Name: Simpler Redirects
 * Description: This plugin allows you to specify redirections from one URL to another. You can customize how many redirections you want to enter.
 * Version: 0.3
 * Author: spacecodes
 * Author URI: https://profiles.wordpress.org/spacecodes/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: simpler-redirects
 */

namespace SIRE_Simpler_Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// define parameters
define( "SIRE_PLUGIN_PATH", plugin_dir_path( __FILE__ ) );
define( "SIRE_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
define( "SIRE_PLUGIN_BASE_NAME", plugin_basename( __FILE__ ) );

// form id
define( "SIRE_FORM_ID", 133 );

// field ids
define( "SIRE_FROM_URL_1", 41 );
define( "SIRE_TO_URL_1", 42 );


// the class for this plugin
class SIRE_Simpler_Redirects {
		
	function __construct() {
		
	}
	
	function onActivate() {
		flush_rewrite_rules();
	}
	
	function onDeactivate() {
		flush_rewrite_rules();
	}

	function onUninstall() {
		// delete all entries from wp_options with the name 'simpler_redirects_%'
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'simpler_redirects_%'" );

		flush_rewrite_rules();
	}
	
	function init() {		
		// enqueue scripts (css, js)
		add_action( "admin_enqueue_scripts", array( $this, "enqueueFiles" ) );
		
		// menu entry
		add_action( "admin_menu", array( $this, "addAdminPagesToMenu" ) );
		
		// add plugin action link
		add_filter( "plugin_action_links_" . SIRE_PLUGIN_BASE_NAME, array( $this, "addPluginActionsLink") );
	}
	
	function enqueueFiles() {
		wp_enqueue_style( "simpler-redirects-style", SIRE_PLUGIN_URL . "assets/simpler_redirects_style.css", array(), "1.0" );
		//wp_enqueue_script( "simpler-redirects-script", SIRE_PLUGIN_URL . "assets/simpler_redirects_script.js", array(), false, "all" );
	}
	
	function addAdminPagesToMenu() {
		add_menu_page( "Redirects", "Redirects", "edit_posts", "simpler_redirects", array( $this, "renderEditEntries" ), "dashicons-admin-settings", 3 );
		
		// sub menus
		//add_submenu_page( 'simpler_redirects', 'Überblick', 'Überblick', 'edit_posts', 'simpler_redirects', array( $this, "renderOverview" ) );
	}
	
	function addPluginActionsLink( $links ) {
		$links[] = "<a href='/wp-admin/admin.php?page=simpler_redirects'>Redirects</a>";
		return $links;
	}
	
	function renderEditEntries() {
		require_once( SIRE_PLUGIN_PATH . "templates/edit_entries.php" );
	}
		
}

// init class
if ( ! class_exists( "SIRE_Simpler_Redirects\SIRE_Simpler_Redirects" ) ) {
	return;
}

$sire_simpler_redirects = new SIRE_Simpler_Redirects();
$sire_simpler_redirects->init();

// set hooks for basic WordPress functions
register_activation_hook( __FILE__, array( $sire_simpler_redirects, "onActivate" ) );
register_deactivation_hook( __FILE__, array( $sire_simpler_redirects, "onDeactivate" ) );


// ___________________________________________________________________________________________ \\
// === PERFORM REDIRECTS ===================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// read all redirects from database and loop through them
function get_redirects() {
	global $wpdb;

	$redirects   = array();
	$fromUrls = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE option_name LIKE 'simpler_redirects_from_url%'", ARRAY_A );
	$toUrls = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE option_name LIKE 'simpler_redirects_to_url%'", ARRAY_A );

	$n = 0;
	foreach ( $fromUrls as $fromUrl ) {
		if ( ! isset( $redirects[ $n ] ) ) {
			$redirects[ $n ] = array();
		}

		$redirects[ $n ]["from_url"] = $fromUrl["option_value"];

		++$n;
	}

	$n = 0;
	foreach ( $toUrls as $toUrl  ) {
		if ( ! isset($redirects[ $n ]) ) {
			$redirects[ $n ] = array();
		}

		$redirects[ $n ]["to_url"] = $toUrl["option_value"];

		++$n;
	}

	return $redirects;
}

// perform redirects function
function perform_redirects() {
	$redirects = get_redirects();

	$scheme = sanitize_text_field( $_SERVER["REQUEST_SCHEME"] );
	$host = sanitize_text_field( $_SERVER["HTTP_HOST"] );
	$uri = sanitize_text_field( $_SERVER["REQUEST_URI"] );
	$fullUrl = $scheme . "://" . $host . $uri;

	foreach ( $redirects as $redirect ) {
		$fromUrl = isset( $redirect["from_url"] ) ? $redirect["from_url"] : "";
		$toUrl = isset( $redirect["to_url"] ) ? $redirect["to_url"] : "";

		if ( $fullUrl == $fromUrl ) {
			header( "Location: " . esc_url($toUrl), true, 302 );
			exit();
		}
	}
}

// perform redirects
add_action( "init", "SIRE_Simpler_Redirects\perform_redirects" );


// ___________________________________________________________________________________________ \\
// === HELPER FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// function to echo a bad message
function sire_echo_bad_message( $message ) {
    echo "<div style='color: red; padding: 10px; margin: 10px;'>❌ " . esc_html( $message ) . "</div>";
}

// function to echo a good message
/*function sire_echo_good_message( $message ) {
	$message = esc_html( $message );
    echo "<div style='color: green; padding: 10px; margin: 10px;'>✔ $message</div>";
}*/