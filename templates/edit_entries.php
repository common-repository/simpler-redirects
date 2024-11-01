<?php
// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

namespace SIRE_Simpler_Redirects;

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

$sire_http_host = sanitize_text_field( $_SERVER["HTTP_HOST"] );

//$myUserId = get_current_user_id();
//$myUserObject = wp_get_current_user();
//$myUserName = $myUserObject->user_login;

// get current domain
$sire_show_success_message = false;
$sire_show_error_message   = false;

// read numUrls from database
$sire_num_urls = get_option( 'simpler_redirects_num_urls' );

// if empty, set to 3
if ( ! $sire_num_urls ) {
	$sire_num_urls = 3;

	// save numUrls to database
	update_option( 'simpler_redirects_num_urls', $sire_num_urls );
}


// ___________________________________________________________________________________________ \\
// === READ ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$sire_redirects = array();

for ( $n = 1; $n <= $sire_num_urls; $n++ ) {
	// read from wp_options the option_value of option_name = 'simpler_redirects_from_url_1' and 'simpler_redirects_to_url_1'
	$sire_from_url = get_option( 'simpler_redirects_from_url_' . $n );
	$sire_to_url   = get_option( 'simpler_redirects_to_url_' . $n );

	// if both are empty, set default values
	if ( empty( $sire_from_url ) && empty( $sire_to_url ) ) {
		$sire_from_url = "";
		$sire_to_url   = "";
	}

	$sire_redirects[ $n ] = array(
		"from_url" => $sire_from_url,
		"to_url"   => $sire_to_url
	);
}


// ___________________________________________________________________________________________ \\
// === UPDATE ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$sire_invalid_entries = array();

do if ( isset( $_POST["simpler_redirects_submit"] ) ) {
	// go through the "from urls" first
	for ( $n = 1; $n <= $sire_num_urls; $n++ ) {
		// skip if not set
		if ( ! isset( $_POST["fromUrl{$n}"] ) ) {
			continue;
		}

		// process the input first to escape and sanitize it
		$sire_from_url =  sanitize_url( $_POST["fromUrl{$n}"] );

		// keep the current processed value as the next input value
		$sire_redirects[ $n ]["from_url"] = $sire_from_url;

		// validate the url input, if not valid -> skip this entry and remember it for later
		if ( empty( $sire_from_url ) ) {
			// save to database into wp_options as option_name = 'simpler_redirects_from_url_1' and option_name = 'simpler_redirects_to_url_1' respectively
			update_option( "simpler_redirects_from_url_{$n}", "" );
		} else if ( filter_var( $sire_from_url, FILTER_VALIDATE_URL ) ) {
			update_option( "simpler_redirects_from_url_{$n}", $sire_from_url );
		} else {
			$sire_invalid_entries[ $n ]["from"] = true;
		}
	}

	// go through the "to urls" after that
	for ( $n = 1; $n <= $sire_num_urls; $n++ ) {
		// skip if not set
		if ( ! isset( $_POST["toUrl{$n}"] ) ) {
			continue;
		}

		// process the input first to escape and sanitize it
		$sire_to_url =  sanitize_url( $_POST["toUrl{$n}"] );

		// keep the current processed value as the next input value
		$sire_redirects[ $n ]["to_url"] = $sire_to_url;

		// validate the url input, if not valid -> skip this entry and remember it for later
		if ( empty( $sire_to_url ) ) {
			update_option( "simpler_redirects_to_url_{$n}", "" );
		} else if ( filter_var( $sire_to_url, FILTER_VALIDATE_URL ) ) {
			// save to database into wp_options as option_name = 'simpler_redirects_from_url_1' and option_name = 'simpler_redirects_to_url_1' respectively
			update_option( "simpler_redirects_to_url_{$n}", $sire_to_url );
		} else {
			$sire_invalid_entries[ $n ]["to"] = true;
		}
	}

	// save number of urls based on $_POST["numUrls"]
	$sire_user_input_num_urls = sanitize_text_field( $_POST["numUrls"] );

	// validate the num urls input and update the option
	// validate the input
	if ( is_numeric( $sire_user_input_num_urls ) && $sire_user_input_num_urls >= 1 && $sire_user_input_num_urls <= 20 ) {
		$sire_num_urls = $sire_user_input_num_urls;
		update_option( "simpler_redirects_num_urls", $sire_num_urls );
	} else {
		$sire_invalid_entries["numUrls"] = true;
	}

	if ( $sire_invalid_entries ) {
		$sire_show_error_message = true;
	} else {
		$sire_show_success_message = true;
	}
} while ( false );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<h1>" . esc_html("Specify up to {$sire_num_urls} redirects") . "</h1>";

echo "<div>";
echo "<form enctype='multipart/form-data' method='POST' class='simpler_redirects_form'>";

// input for number of redirects
echo "<div class='simpler_redirects_input_container'>";
echo "<label for='numUrls'>Number of redirects (1-20): </label>";
echo "<input type='number' name='numUrls' id='numUrls' value='" . esc_attr($sire_num_urls) . "' min='1' max='20' step='1' class='simpler_redirects_input' />";

// show error message
if ( isset( $sire_invalid_entries["numUrls"] ) && $sire_invalid_entries["numUrls"] ) {
	sire_echo_bad_message( "Invalid num urls input. Please check the input." );
}
echo "</div>";

// gap
echo '<div class="break"></div>';

// gap with br
echo '<br />';

for ( $n = 1; $n <= $sire_num_urls; $n++ ) {
	// set the current values
	$sire_redirect  = isset( $sire_redirects[ $n ] ) ? $sire_redirects[ $n ] : array( "from_url" => "", "to_url" => "" );

	echo "<div class='simpler_redirects_url'>";

	// caption
	echo "<div class='simpler_redirects_caption'>";
	echo "<h2>" . esc_html ("Redirection #{$n}") . "</h2>";
	echo "</div>";

	echo "<div class='simpler_redirects_url_from'>";
	echo "<label for='" . esc_html("fromUrl{$n}") . "'>From here</label>";
	echo "<br>";
	echo "<input type='text' name='" . esc_html("fromUrl{$n}") . "' value='" . esc_url($sire_redirect["from_url"])  . "' placeholder='" . esc_url("https://{$sire_http_host}/from_here") . "'/>";
	if ( isset( $sire_invalid_entries[ $n ]["from"] ) && $sire_invalid_entries[ $n ]["from"] ) {
		sire_echo_bad_message( "Invalid url. Please check the input." );
	}
	echo "</div>";
	echo "<div class='simpler_redirects_url_to'>";
	echo "<label for='" . esc_html("toUrl{$n}") . "'>To here (destination)</label>";
	echo "<br>";
	echo "<input type='text' name='" . esc_html("toUrl{$n}") . "' value='" . esc_url($sire_redirect["to_url"]) . "' placeholder='" . esc_url("https://{$sire_http_host}/to_destination") . "'/>";
	if ( isset( $sire_invalid_entries[ $n ]["to"] ) && $sire_invalid_entries[ $n ]["to"] ) {
		sire_echo_bad_message( "Invalid url. Please check the input." );
	}
	echo "</div>";
	echo "</div>";
}

// gap
echo '<div class="break"></div>';

// info message that only absolute paths are allowed
echo "<div class='simpler_redirects_info'>";
echo "Only absolute paths are allowed. Example: " . esc_url("https://{$sire_http_host}/to_destination");
echo "</div>";

// gap
echo '<div class="break"></div>';
echo '<br>';

// submit button
echo "<input type='submit' name='simpler_redirects_submit' value='Save'>";

// success message
if ( $sire_show_success_message ) {
	echo '<div class="simpler_redirects_success">
	        <div class="icon">
	            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
	                <path d="M504.5 171.95l-36.2-36.41c-10-10.05-26.21-10.05-36.2 0L192 377.02 79.9 264.28c-10-10.06-26.21-10.06-36.2 0L7.5 300.69c-10 10.05-10 26.36 0 36.41l166.4 167.36c10 10.06 26.21 10.06 36.2 0l294.4-296.09c10-10.06 10-26.36 0-36.42z"/>
				</svg>
	        </div>
	        <div class="message">
	            Success! Your changes have been saved.
	        </div>
		</div>';
} else if ( $sire_show_error_message ) {
	echo '<div class="simpler_redirects_failure"><div class="icon">';
	echo wp_kses_post( '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' );
    echo '</div>';
    echo '<div class="message">An error occurred! Please check the fields for error messages.</div></div>';
}

echo "</form>";