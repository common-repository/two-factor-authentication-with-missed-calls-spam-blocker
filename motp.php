<?
/*
Plugin Name: mOTP Spam Blocker
Plugin URI:  http://www.motp.in
Description: Plugin for implementing two factor authentication with missed calls. Verification can be set on various screens as per users choice.
Author: motp
Version: 1.0
Author URI: http://www.motp.in/

*/

/*  © Copyright 2014 Copyright

*/


// These fields are to Enable/Disable Verification in various areas
require_once("motp_session.php");
$motp_admin_fields_enable = array (
		array( 'motp_login_form', __( 'Login form', 'motp' ), __( 'Login form', 'motp' ) ),
		array( 'motp_register_form', __( 'Register form', 'motp' ), __( 'Register form', 'motp' ) ),
		array( 'motp_lost_password_form', __( 'Lost password form', 'motp' ), __( 'Lost password form', 'motp' ) ),
		array( 'motp_comments_form', __( 'Comments form', 'motp' ), __( 'Comments form', 'motp') ),
		array( 'motp_hide_register', __( 'No Verification for registered users', 'motp' ), __( 'No Verification for registered users', 'motp' ) ),		
);

if( ! function_exists( 'motp_add_menu_render' ) ) {
	function motp_add_menu_render() {
		global $title;
		$active_plugins = get_option('active_plugins');
		$all_plugins		= get_plugins();

		echo "<br><p><p><h3>motp Quick Links</h3>Click <a href='admin.php?page=motp.php'>Here</a> to configure the plugin.<br />Click <a href='http://motp.in/Documentation.html'  target=_blank>Here</a> to register at mOTP.</p>";
		}
}
function motp2_install () {
global $wpdb;
 $table_name = $wpdb->prefix . "verifications2";
      
   $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ts` datetime NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `pin` varchar(50) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `session` varchar(255) NOT NULL,
  `trials` int(2) unsigned NOT NULL,
  `ttime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session` (`session`)
) ENGINE=MyISAM ";

   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql );
 
}
function add_motp_admin_menu() {
	add_menu_page( 'motp Plugin', 'motp Plugin', 'manage_options', 'motp_plugins', 'motp_add_menu_render', '', 1001); 
	add_submenu_page('motp_plugins', __( 'Plugin Settings', 'motp' ), __( 'Configure', 'Register' ), 'manage_options', "motp.php", 'motp_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_motp_settings' );
}

// register settings function
function register_motp_settings() {

	global $wpmu;
	global $motp_options;

	$motp_option_defaults = array(
		'motp_login_form'						=> '1',
		'motp_register_form'					=> '1',
		'motp_lost_password_form'		=> '1',
		'motp_comments_form'					=> '1',
		'motp_hide_register'					=> '1',
		'motp_math_action_plus'			=> '1',
		'motp_math_action_minus'			=> '1',
		'motp_math_action_increase'	=> '1',
		'motp_label_form'						=> 'mOTP Spam Blocker',
		'motp_pubKey'						=> '',
		'motp_privateKey'						=> '',
		'motp_maxretry'						=> '',
		'motp_retrytm'						=> '',
		'motp_blacklist'						=> '',
		'motp_ipblacklist'						=> '',
  );

  // install the option defaults
	if ( 1 == $wpmu ) {
		if( !get_site_option('motp_options')) {
			add_site_option('motp_options', $motp_option_defaults, '', 'yes' );
		}
	} 
	else {
		if( !get_option('motp_options'))
			add_option('motp_options', $motp_option_defaults, '', 'yes' );
	}

  // get options from the database
  if ( 1 == $wpmu )
   $motp_options = get_site_option( 'motp_options' ); // get options from the database
  else
   $motp_options = get_option( 'motp_options' );// get options from the database

  // array merge incase this version has added new options
  $motp_options = array_merge( $motp_option_defaults, $motp_options );
}

// Add global setting for verification
global $wpmu;

if ( 1 == $wpmu )
   $motp_options = get_site_option( 'motp_options' ); // get the options from the database
  else
   $motp_options = get_option( 'motp_options' );// get the options from the database
   
// Add verification into login form
if( 1 == $motp_options['motp_login_form'] ) {
	add_action( 'login_form', 'motp_login_form' );
	add_filter( 'login_errors', 'motp_login_post' );
	add_filter( 'login_redirect', 'motp_login_check', 10, 3 ); 
}
// Add verification into comments form
if( 1 == $motp_options['motp_comments_form'] ) {
	global $wp_version;
	if( version_compare($wp_version,'3','>=') ) { // wp 3.0 +
		add_action( 'comment_form_after_fields', 'motp_comment_form_wp3', 1 );
		add_action( 'comment_form_logged_in_after', 'motp_comment_form_wp3', 1 );
	}	
	// for WP before WP 3.0
	add_action( 'comment_form', 'motp_comment_form' );
	add_filter( 'preprocess_comment', 'motp_comment_post' );	
}
// Add verification in the register form
if( 1 == $motp_options['motp_register_form'] ) {
	add_action( 'register_form', 'motp_register_form' );
	add_action( 'register_post', 'motp_register_post', 10, 3 );
}
// Add verification into lost password form
if( 1 == $motp_options['motp_lost_password_form'] ) {
	add_action( 'lostpassword_form', 'motp_register_form' );
	add_action( 'lostpassword_post', 'motp_lostpassword_post', 10, 3 );
}
register_activation_hook( __FILE__, 'motp2_install' );
function motp_plugin_action_links( $links, $file ) {
		//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

	if ( $file == $this_plugin ){
			 $settings_link = '<a href="admin.php?page=motp.php">' . __( 'Settings', 'motp' ) . '</a>';
			 array_unshift( $links, $settings_link );
		}
	return $links;
} // end function motp_plugin_action_links

function motp_register_plugin_links($links, $file) {
	$base = plugin_basename(__FILE__);
	if ($file == $base) {
		$links[] = '<a href="admin.php?page=motp.php">' . __( 'Settings', 'motp' ) . '</a>';
		//$links[] = '<a href="#" target="_blank">' . __( 'FAQ', 'motp' ) . '</a>';
		$links[] = '<a href="http://dial2verify.com/corp/contactus.html" target="_blank">' . __( 'Support', 'motp' ) . '</a>';
	}
	return $links;
}

// Function for display verification settings page in the admin area
function motp_settings_page() {
	global $motp_admin_fields_enable;
	global $motp_admin_fields_actions;
	global $motp_admin_fields_difficulty;
	global $motp_options;
	$iscurl  = function_exists('curl_version') ? 'Enabled' : 'Disabled';
	$isfile = file_get_contents(__FILE__) ? 'Enabled' : 'Disabled';
	$error = "";
	
	// Save data for settings page
	if( isset( $_REQUEST['motp_form_submit'] ) ) {
		$motp_request_options = array();

		foreach( $motp_options as $key => $val ) {
			if( isset( $_REQUEST[$key] ) ) {
				if( ($key == 'motp_login_form') || ($key == 'motp_register_form') || ($key == 'motp_lost_password_form') || ($key == 'motp_comments_form') || ($key == 'motp_hide_register'))
					$motp_request_options[$key] = 1;
				else
					$motp_request_options[$key] = $_REQUEST[$key];
			} else {
				if(($key == 'motp_login_form') || ($key == 'motp_register_form') || ($key == 'motp_lost_password_form') || ($key == 'motp_comments_form') || ($key == 'motp_hide_register'))
					$motp_request_options[$key] = 0;
				else
					$motp_request_options[$key] = "";
			}
		}

		// array merge incase this version has added new options
		$motp_options = array_merge( $motp_options, $motp_request_options );

			// Update options in the database
			update_option( 'motp_options', $motp_request_options, '', 'yes' );
			$message = __( "Options are updated", 'motp' );
	}

	// Display form on the setting page
?>
<div class="wrap">
	
	<div class="iconi icoimg" id="icoimg"></div>
	<h2><?php _e('motp Settings', 'motp' ); ?></h2>
	<div class='updated'><h3>Server Settings</h3>file_get_contents: <?=$isfile?> <br>
	curl: <?=$iscurl?> <? if ($iscurl=="Disabled" && $isfile=="Disabled") echo "you need to enable file_get_contents or curl functions in php.ini in order to use this plugin"; else echo "<br>Server settings are ok";?></div>
	<div class="updated fade" <?php if( ! isset( $_REQUEST['motp_form_submit'] ) || $error != "" ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
	<div class="error" <?php if( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
	<form method="post" action="admin.php?page=motp.php">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Enable Verification for :', 'motp' ); ?> </th>
				<td>
			<?php foreach( $motp_admin_fields_enable as $fields ) { ?>
					<input type="checkbox" name="<?php echo $fields[0]; ?>" value="<?php echo $fields[0]; ?>" <?php if( 1 == $motp_options[$fields[0]] ) echo "checked=\"checked\""; ?> /><label for="<?php echo $fields[0]; ?>"><?php echo __( $fields[1], 'motp' ); ?></label><br />
			<?php } 
			$active_plugins = get_option('active_plugins');
			$all_plugins = get_plugins();
			 ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Label for Verification area : ', 'motp' ); ?></th>
				<td><textarea name="motp_label_form" rows="4" cols="50"><?php echo stripslashes( $motp_options['motp_label_form'] ); ?></textarea>"  </td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'motp Public Key : ', 'motp' ); ?></th>
				<td><input type="text" name="motp_pubKey" value="<?php echo stripslashes( $motp_options['motp_pubKey'] ); ?>"  />&nbsp;&nbsp;<br>If you dont have an account, register <a href="http://motp.in/Documentation.html" target="_blank">from here</a></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'motp Private Key : ', 'motp' ); ?></th>
				<td><input type="text" name="motp_privateKey" value="<?php echo stripslashes( $motp_options['motp_privateKey'] ); ?>"  /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Max Retries : ', 'motp' ); ?></th>
				<td><input type="text" name="motp_maxretry" value="<?php echo stripslashes( $motp_options['motp_maxretry'] ); ?>" /></td>
			</tr>
						<tr valign="top">
				<th scope="row"><?php _e( 'Retry Time : ', 'motp' ); ?></th>
				<td><input type="text" name="motp_retrytm" value="<?php echo stripslashes( $motp_options['motp_retrytm'] ); ?>" /> seconds</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Black List : ', 'motp' ); ?></th>
				<td><textarea name="motp_blacklist" rows="4" cols="50"><?php echo stripslashes( $motp_options['motp_blacklist'] ); ?></textarea> <br>Seperate by commas or new line</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'IP Black List : ', 'motp' ); ?></th>
				<td><textarea name="motp_ipblacklist" rows="4" cols="50"><?php echo stripslashes( $motp_options['motp_ipblacklist'] ); ?></textarea> <br>Seperate by command or new line</td>
			</tr>
		</table>    
		<input type="hidden" name="motp_form_submit" value="submit" />
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
<?php } 

// this function adds verification to the login form
function motp_login_form() {
	global $motp_options;
	if( isset( $_SESSION['motp_error'] ) ) {
		echo "<br /><span style='color:red'>". $_SESSION['motp_error'] ."</span><br />";
		unset( $_SESSION['motp_error'] );
	}
	if (get_ver_field("status")!=3) motp_display_verification();
	return true;

} //  end function motp_login_form

// this function checks verification posted with a login
function motp_login_post($errors) {
	global $str_key;
	
	// Delete errors, if they set
	if( isset( $_SESSION['motp_error'] ) )
		unset( $_SESSION['motp_error'] );
	if (get_ver_field("status")!=3) {
		if( isset( $_REQUEST['action'] ) && 'register' == $_REQUEST['action'] )
			return($errors);
	 $pin = get_ver_field("pin");
	// If verification not complete, return error
		if ( !$pin) {	
			return $errors.'<strong>'. __( 'ERROR', 'motp' ) .'</strong>: '. __( 'Please complete the verification.', 'motp' );
		}

	//if ($_REQUEST['motp_result']== $_REQUEST['motp_number']) {
		if ($pin == trim($_REQUEST['motp_number'])){
			set_ver_field("status", 3);
		// verification was matched						
		} else {
			return $errors.'<strong>'. __( 'ERROR', 'motp' ) .'</strong>: '. __( 'Verification code is incorrect.', 'motp' );
		}
	}
  return($errors);
} // end function motp_login_post

// this function checks the verification posted with a login when login errors are absent
function motp_login_check($url) {
	global $str_key;
	if (get_ver_field("status")!=3) {
	$pin = get_ver_field("pin");
	// Add error if verification is empty
	if ( !$pin) {
		$_SESSION['motp_error'] = __( 'Please complete the verifications.', 'motp' );
		// Redirect to wp-login.php
		return $_SERVER["REQUEST_URI"];
	}
	
		if ($pin== $_REQUEST['motp_number']) {
			set_ver_field("status", 3);
			return $url;		// verification was matched						
		} else {
			// Add error if verification is incorrect
			$_SESSION['motp_error'] = __('Verification code is incorrect.', 'motp');
			// Redirect to wp-login.php
			return $_SERVER["REQUEST_URI"];
		}
	}
	else {
		return $url;		// verification was matched		
		//$_SESSION["user_verfied"]=1;		
	}
} // end function motp_login_post

// this function adds verification to the comment form
function motp_comment_form() {
	global $motp_options;

	// skip verification if user is logged in and the settings allow
	if ( is_user_logged_in() && 1 == $motp_options['motp_hide_register'] ) {
		return true;
	}

	// verification html - comment form
	
	if (get_ver_field("status")!=3) motp_display_verification();
	

	return true;
} // end function motp_comment_form

// this function adds verification to the comment form
function motp_comment_form_wp3() {
	global $motp_options;

	// skip verification if user is logged in and the settings allow
	if ( is_user_logged_in() && 1 == $motp_options['motp_hide_register'] ) {
		return true;
	}

		// verification html - comment form
	
	if (get_ver_field("status")!=3) motp_display_verification();
	
	remove_action( 'comment_form', 'motp_comment_form' );

	return true;
} // end function motp_comment_form


// this function checks verification posted with the comment
function motp_comment_post($comment) {	
	global $motp_options;

	if ( is_user_logged_in() && 1 == $motp_options['motp_hide_register'] ) {
		return $comment;
	}
    
	global $str_key;
	$str_key = "123";
	// added for compatibility with WP Wall plugin
	// this does NOT add verification to WP Wall plugin,
	// it just prevents the "Error: You did not enter a verification phrase." when submitting a WP Wall comment
	if ( function_exists( 'WPWall_Widget' ) && isset( $_REQUEST['wpwall_comment'] ) ) {
			// skip verification
			return $comment;
	}

	// skip verification for comment replies from the admin menu
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'replyto-comment' &&
	( check_ajax_referer( 'replyto-comment', '_ajax_nonce', false ) || check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment', false ) ) ) {
				// skip verification
				return $comment;
	}

	// Skip verification for trackback or pingback
	if ( $comment['comment_type'] != '' && $comment['comment_type'] != 'comment' ) {
						 // skip verification
						 return $comment;
	}
	
	// If verification is empty
	if (get_ver_field("status")!=3){
	$pin = get_ver_field("pin");
	if ( !$pin)
		wp_die( __('Please complete the verification.', 'motp' ) );

	if ($pin== $_REQUEST['motp_number']) {
		// verification was matched
		set_ver_field("status",3);
		return($comment);
	} else {
		wp_die( __('Error: Verification code is incorrect', 'motp'));
	}
	}
	else return($comment);
} // end function motp_comment_post

// this function adds the verification to the register form

function motp_register_form() {
	global $motp_options;
	if (get_ver_field("status")!=3) motp_display_verification();
	return true;
} // end function motp_register_form

// this function checks verification posted with registration
function motp_register_post($login,$email,$errors) {

	global $motp_options;
	// No pin - add error
	if (get_ver_field("status")!=3) {
	$pin = get_ver_field("pin");
	if ( !$pin) {
		$errors->add('verification_blank', '<strong>'.__('ERROR', 'motp').'</strong>: '.__('Please complete the verification.', 'motp'));
		return $errors;
	}
	if ($pin== $_REQUEST['motp_number']) {
		//$_SESSION["verified"]= 1;
		set_ver_field("status", 3);
					// Pin matched
	} else {
		$errors->add('verification_wrong', '<strong>'.__('ERROR', 'motp').'</strong>: '.__('Verification code is incorrect', 'motp'));
	}
	}
  return($errors);
} // end function motp_register_post

// this function checks the verification posted with lostpassword form
function motp_lostpassword_post() {
	

	// If field 'user login' is empty - return
	

	// If verification doesn't entered
	if (get_ver_field("status")!=3) {
	$pin = get_ver_field("pin");
  if ( !$pin) {
		wp_die( __( 'Please complete the verification.', 'motp' ) );
	}
	
	// Check entered verification
	if ($pin== $_REQUEST['motp_number']) {
		set_ver_field("status", 3);
		return;
	} else {
		wp_die( __( 'Error: Verification code is incorrect', 'motp' ) );
	}
	}
	if( isset( $_REQUEST['user_login'] ) && "" == $_REQUEST['user_login'] )
		return;
} // function motp_lostpassword_post

// Functionality of the verification logic work
function motp_display_verification()
{

	global $motp_options;
	

	$label = $motp_options['motp_label_form'];
	$type = $motp_options['motp_vertype'];
	
	create_session();
	$status = get_ver_field("status");
    
        
	
	$frmtxt .= "<script>var status='{$status}'</script><div style='background-color:#b0c4de; padding: 5px 5px 5px 5px;border:1px solid;border-radius:2px;-moz-border-radius:2px;' ><div style='background-color:black;color:white;padding: 5px 5px 5px 5px;'><strong> $label</strong></div><div id=\"step1\" class=\"input-container\" style='margin-top:5px'><label for=\"user_login\"><b>Phone Number ( with ISD Code ):</b></label><br /><br />";
	$frmtxt .= "<input type=\"text\" name=\"telephone\" id=\"telephone\" maxlength=\"12\" value=\"\" size=\"12\" style=\"margin-bottom:0;width:65%\" /><input id=\"driver\" type=\"button\" class=\"button button-large\" style='line-height:normal;' value=\"verify\" />";
	$frmtxt .= "</div><div id=\"step2\" style=\"display:none;margin-top:5px\"><label for=\"user_login\">mOTP:</label><br />";
	$frmtxt .= "<input type=\"text\" class=\"input\" name=\"motp_number\" id=\"motp_number\" maxlength=\"12\" value=\"123456XXXX\" size=\"12\" style=\"margin-bottom:0;display:inline;width:65%\" /> <input id=\"driver2\" type=\"button\" class=\"button button-large\" style='line-height:normal;' value=\"Re Try\" />";
	$frmtxt .= "<input type='hidden' name='motp_result' id='mipin' value='$ipin' ></div>";
	$frmtxt .= "<div  style=\" color: red; margin: 2px 2px 2px 2px \"> <span id='stage' ></span></div><br><hr style='background-color:black;'><p align=right><font size=1>Powered by <a href=\"http://motp.in\" target=\"_blank\">mOTP</a></font></p></div> <br>";
	if (get_ver_field("status")!=3) echo $frmtxt; 
	?>
<?php
}

// Functionality of the verification logic work for custom form

function motp_contact_form_options()
{
	if( function_exists( 'get_plugins' ) ) {
		$all_plugins = get_plugins();
		if( array_key_exists('contact-form-plugin/contact_form.php', $all_plugins) )
		{
			$motp_options = get_option( 'motp_options' );
			if( $motp_options['motp_contact_form'] == 1) {
				add_filter('cntctfrm_display_verification', 'motp_custom_form');
				add_filter('cntctfrm_check_form', 'motp_check_custom_form');
			}
			else if( $motp_options['motp_contact_form'] == 0 ) {
				remove_filter('cntctfrm_display_verification', 'motp_custom_form');
				remove_filter('cntctfrm_check_form', 'motp_check_custom_form');
			}
		}
	} 
	else {
		$active_plugins = get_option('active_plugins');
		if(0 < count( preg_grep( '/contact-form-plugin\/contact_form.php/', $active_plugins ) ) ) { 
			$motp_options = get_option( 'motp_options' );
			if( $motp_options['motp_contact_form'] == 1) {
				add_filter('cntctfrm_display_verification', 'motp_custom_form');
				add_filter('cntctfrm_check_form', 'motp_check_custom_form');
			}
			else if( $motp_options['motp_contact_form'] == 0 ) {
				remove_filter('cntctfrm_display_verification', 'motp_custom_form');
				remove_filter('cntctfrm_check_form', 'motp_check_custom_form');
			}
		}
	}
}

if ( ! function_exists ( 'motp_plugin_init' ) ) {
	function motp_plugin_init() {
	// Internationalization, first(!)
	load_plugin_textdomain( 'motp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

	// Other init stuff, be sure to it after load_plugins_textdomain if it involves translated text(!)
	}
}




// adds "Settings" link to the plugin action page
add_filter( 'plugin_action_links', 'motp_plugin_action_links', 10, 2 );

//Additional links on the plugin page
add_filter( 'plugin_row_meta', 'motp_register_plugin_links', 10, 2 );

add_action( 'init', 'motp_plugin_init' );
add_action( 'admin_init', 'motp_plugin_init' );
add_action( 'admin_init', 'motp_contact_form_options' );
add_action( 'admin_menu', 'add_motp_admin_menu' );
add_action( 'after_setup_theme', 'motp_contact_form_options' );

wp_enqueue_script( 'my-ajax-request', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ) );
wp_localize_script( 'my-ajax-request', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
do_action( 'wp_ajax_' . $_POST['action'] );
add_action( 'wp_ajax_nopriv_myajax-submit', 'myajax_submit' );
add_action( 'wp_ajax_myajax-submit', 'myajax_submit' );

function myajax_submit() {
	global $wpdb;
	header( "Content-Type: application/json" );
	
	include "motp_push.php";
	exit;
}
?>