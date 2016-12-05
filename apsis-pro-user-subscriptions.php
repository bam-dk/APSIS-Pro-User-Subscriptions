<?php
/*
Plugin Name: APSIS Pro User Subscriptions
Description: Plugin for mapping APSIS Pro Demographic Data Fields and to make it possible to change a user's APSIS Pro subscriptions.
Version: 1.0
Author: iqq
Author URI: http://www.iqq.se/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * APSIS Pro for WP
 */
class APSIS_Pro_User_Subscriptions {

	/**
	 * Initialize hooks
	 */
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'child_plugin_has_parent_plugin' ) );
		add_action( 'admin_menu', array( __CLASS__, 'apsispro_us_add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'apsispro_us_settings_init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'apsispro_us_enqueue_backend_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'apsispro_us_enqueue_frontend_scripts' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'wp_ajax_nopriv_apsispro_us_action', array( __CLASS__, 'apsispro_action_us_callback' ) );
		add_action( 'wp_ajax_apsispro_us_action', array( __CLASS__, 'apsispro_action_us_callback' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'user_register', array( __CLASS__, 'apsispro_us_registration_save'), 10, 1 );
		add_action( 'profile_update', array( __CLASS__, 'apsispro_us_profile_update'), 10, 2 );
		add_action( 'set_user_role', array( __CLASS__, 'apsispro_us_role_change'), 20, 2 );
		add_action( 'add_user_role', array( __CLASS__, 'apsispro_us_role_change'), 30, 2 );


	}

	/**
	 * Check if APSIS Pro for WP plugin is activated, display error message otherwise.
	 */
	public static function child_plugin_has_parent_plugin() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !class_exists( 'APSIS_Pro_For_WP' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'child_plugin_notice' ) );

			deactivate_plugins( plugin_basename( __FILE__ ) );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}

		if( class_exists( 'APSIS_Pro_For_WP' ) ) {
			require_once ABSPATH . '/wp-content/plugins/APSIS-Pro-for-WP/apsis-pro-for-wp.php';
		}

	}

	/**
	 * Error message when APSIS Pro for WP plugin isn't activated
	 */
	public static function child_plugin_notice() { ?>

		<div class="error"><p><?php _e( 'Sorry, but APSIS Pro User Subscriptions requires the APSIS Pro for WP plugin to be installed and active.', 'apsispro' ) ?></p></div>

	<?php }

	/**
	 * Enqueue backend scripts
	 */
	public static function apsispro_us_enqueue_backend_scripts() {

		wp_enqueue_script( 'apsispro-us-backend', plugins_url( '/js/backend.min.js', __FILE__ ), array( 'jquery' ) );

		$options = get_option( 'apsispro_settings' );
		if ( $options['apsispro_hidden_verified'] ) :
			$verified = 1;
		else:
			$verified = 0;
		endif;

		wp_localize_script( 'apsispro-us-backend', 'ajax_object',
			array(
				'verified' => $verified
			) );

	}

	/**
	 * Enqueue frontend scripts
	 */
	public static function apsispro_us_enqueue_frontend_scripts() {

		wp_enqueue_script( 'apsispro-us-frontend', plugins_url( '/js/frontend.min.js', __FILE__ ), array( 'jquery' ) );

		$options = get_option( 'apsispro_us_settings' );
		if ( $options['apsispro_us_select_default_general'] ) :
			$default_list = $options['apsispro_us_select_default_general'];
		else:
			$default_list = '';
		endif;
		if ( $options['apsispro_us_select_default_subscriber'] ) :
			$default_subscriber_list = $options['apsispro_us_select_default_subscriber'];
		else:
			$default_subscriber_list = '';
		endif;

		wp_localize_script( 'apsispro-us-frontend', 'apsispro_us_ajax_object',
			array(
				'ajax_url'           		=> admin_url( 'admin-ajax.php' ),
				'error_msg_standard' 		=> __( 'An error occurred, please try again later.', 'apsispro' ),
				'error_msg_email'    		=> __( 'The e-mail address is not correct.', 'apsispro' ),
				'error_msg_mailinglist' 	=> __( 'A mailing list needs to be selected.', 'apsispro' ),
				'default_list'				=> $default_list,
				'default_subscriber_list'	=> $default_subscriber_list
			) );

	}

	/**
	 * Load text domain
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'apsispro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add settings page in admin menu
	 */
	public static function apsispro_us_add_admin_menu() {

		add_submenu_page( 'options-general.php', __( 'APSIS Pro User Subscriptions Settings', 'apsispro' ), __( 'APSIS Pro User Subscriptions', 'apsispro' ), 'manage_options', 'apsispro-us-settings', array(
			__CLASS__,
			'apsispro_us_settings_page'
		), 'dashicons-admin-generic' );

	}

	/**
	 * Register settings for settings page
	 */
	public static function apsispro_us_settings_init() {

		register_setting( 'apsispro_us_demo_group', 'apsispro_us_demo_settings' );
		register_setting( 'apsispro_us_demo_group', 'apsispro_us_settings' );

		add_settings_section(
			'apsispro_us_demo_group_section',
			__( 'APSIS Pro User Subscriptions settings', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_demo_settings_section_callback' ),
			'apsispro_us_demo_group'
		);

		add_settings_section(
			'apsispro_us_shortcode_group_section',
			__( 'Shortcode Generator', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_shortcode_settings_section_callback' ),
			'apsispro_us_demo_group'
		);

		add_settings_field(
			'apsispro_us_input_demographic_data_fields',
			__( 'Map Demographic Data Fields', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_input_demographic_data_fields_render' ),
			'apsispro_us_demo_group',
			'apsispro_us_demo_group_section'
		);

		add_settings_field(
			'apsispro_us_select_default_general',
			__( 'Default general list', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_select_default_general_render' ),
			'apsispro_us_demo_group',
			'apsispro_us_demo_group_section'
		);

		add_settings_field(
			'apsispro_us_select_default_subscriber',
			__( 'Default subscriber list', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_select_default_subscriber_render' ),
			'apsispro_us_demo_group',
			'apsispro_us_demo_group_section'
		);

		add_settings_field(
			'apsispro_us_select_role',
			__( 'Role', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_select_role_render' ),
			'apsispro_us_demo_group',
			'apsispro_us_demo_group_section'
		);

		add_settings_field(
			'apsispro_select_mailing_list',
			__( 'Select Mailing List', 'apsispro' ),
			array( __CLASS__, 'apsispro_us_select_mailing_list_render' ),
			'apsispro_us_demo_group',
			'apsispro_us_shortcode_group_section'
		);

	}

	/**
	 * Input fields for mapping of Demographic Data Fields
	 */
	public static function apsispro_us_input_demographic_data_fields_render() {

		$options = get_option( 'apsispro_us_demo_settings' );
		$demo_data_fields = self::get_demographic_data_fields();
		if ( $demo_data_fields !== -1 || ! empty( $demo_data_fields ) ) :
			foreach ( $demo_data_fields as $demo_data_field ) :
				if( ! empty( $options[$demo_data_field] ) ) :
					$current_option = $options[$demo_data_field];
				else:
					$current_option = '';
				endif;
				?>
				<p>
					<label style="width: 140px;display: inline-block;"><?php echo $demo_data_field ?></label>
					<select class="apsispro_us_<?php echo $demo_data_field; ?>" name="apsispro_us_demo_settings[<?php echo $demo_data_field; ?>]">
						<?php echo self::get_user_meta_key($current_option); ?>
					</select>
				</p>
			<?php endforeach;
		endif;

	}

	/**
	 * Select dropdown for selecting default mailinglist
	 */
	public static function apsispro_us_select_default_general_render() {

		$options = get_option( 'apsispro_us_settings' );
		if ( isset( $options['apsispro_us_select_default_general'] ) ) :
			$selected_mailinglist = $options['apsispro_us_select_default_general'];
		else :
			$selected_mailinglist = - 1;
		endif;
		?>

		<select class="apsispro_us_select_default_general" name='apsispro_us_settings[apsispro_us_select_default_general]'>
			<?php
			$mailinglist_items = '';
			$mailinglists = APSIS_Pro_For_WP::get_mailinglists( intval( $selected_mailinglist ) );
			if ( $mailinglists !== false ) :
				foreach ( $mailinglists as $index => $list_item ) { ?>
					<option value="<?php echo $list_item['Id'] ?>"<?php echo ( $selected_mailinglist == $list_item['Id'] ) ? ' selected="selected"' : ''; ?>><?php echo $list_item['Name'] ?></option>
				<?php }
			endif;
			?>
		</select>
		<?php

	}

	/**
	 * Select dropdown for selecting default subscriber mailinglist
	 */
	public static function apsispro_us_select_default_subscriber_render() {

		$options = get_option( 'apsispro_us_settings' );
		if ( isset( $options['apsispro_us_select_default_subscriber'] ) ) :
			$selected_mailinglist = $options['apsispro_us_select_default_subscriber'];
		else :
			$selected_mailinglist = - 1;
		endif;
		?>

		<select class="apsispro_us_select_default_subscriber" name='apsispro_us_settings[apsispro_us_select_default_subscriber]'>
			<?php
			$mailinglist_items = '';
			$mailinglists = APSIS_Pro_For_WP::get_mailinglists( intval( $selected_mailinglist ) );
			if ( $mailinglists !== false ) :
				foreach ( $mailinglists as $index => $list_item ) { ?>
					<option value="<?php echo $list_item['Id'] ?>"<?php echo ( $selected_mailinglist == $list_item['Id'] ) ? ' selected="selected"' : ''; ?>><?php echo $list_item['Name'] ?></option>
				<?php }
			endif;
			?>
		</select>
		<?php

	}

	/**
	 * Select dropdown for selecting user role, when default subscriber mailinglist is used
	 */
	public static function apsispro_us_select_role_render() {

		$options = get_option( 'apsispro_us_settings' );
		$selected_role = $options['apsispro_us_select_role'];
		?>

		<select class="apsispro_us_select_role" name='apsispro_us_settings[apsispro_us_select_role]'>
			<?php
			global $wp_roles;
			$roles = $wp_roles->get_names();
			foreach ( $roles as $role => $role_name ) { ?>
				<option value="<?php echo $role ?>"<?php echo ( $selected_role == $role ) ? ' selected="selected"' : ''; ?>><?php echo $role_name ?></option>
			<?php } ?>
		</select>

		<?php
		submit_button( __( 'Save Settings', 'apsispro' ), 'primary', 'apsispro-us-settings-button' );

	}

	/**
	 * Checkboxes for mailing lists
	 */
	public static function apsispro_us_select_mailing_list_render() {

		$options = get_option( 'apsispro_us_settings' );
		if ( isset( $options['apsispro_us_select_mailing_list'] ) ) :
			$selected_mailinglist = $options['apsispro_us_select_mailing_list'];
		else :
			$selected_mailinglist = - 1;
		endif;
		?>
		<div class="apsispro_us_mailinglist_checkboxes">
			<?php
			$mailinglist_items = '';
			$mailinglists = APSIS_Pro_For_WP::get_mailinglists( intval( $selected_mailinglist ) );
			if ( $mailinglists !== false ) :
				foreach ( $mailinglists as $index => $list_item ) {
					$mailinglist_items .= '<input type="checkbox" id="apsispro_us_mailinglist_checkbox-' . $list_item['Id'] . '" name="' . $list_item['Name'] . '" value="' . $list_item['Id'] . '"><label for="apsispro_us_mailinglist_checkbox-' . $list_item['Id'] . '">' . $list_item['Name'] . '</label><br>';
				}
			endif;
			echo '<p>' . $mailinglist_items . '</p>';
			?>
		</div>
		<?php

	}

	/**
	 * Instructions for the settings fields
	 */
	public static function apsispro_us_demo_settings_section_callback() {

		echo '';

	}

	/**
	 * Instructions for the shortcode generator fields
	 */
	public static function apsispro_us_shortcode_settings_section_callback() {

		echo __( 'Modify the settings and click on <i>Generate Shortcode</i> to generate a shortcode that can be inserted on the site.', 'apsispro' );

	}

	/**
	 * The settings page in admin
	 */
	public static function apsispro_us_settings_page() {

		?>
		<form action='options.php' method='post'>

			<h2>APSIS Pro User Subscriptions</h2>

			<?php
			$options = get_option( 'apsispro_settings' );
			if ( $options['apsispro_hidden_verified'] ) :
				settings_fields( 'apsispro_us_demo_group' );
				do_settings_sections( 'apsispro_us_demo_group' );
				?>
				<div id="apsispro-us-shortcode-generator" style="display: none;">
					<?php
					submit_button( __( 'Generate Shortcode', 'apsispro' ), 'secondary', 'us-generate-shortcode-button' );
					?>

					<table class="form-table">
						<th scope="row">
							<?php echo __( 'Generated shortcode:', 'apsispro' ); ?>
						</th>
						<td>
							<input type='text' id='apsispro-us-generated-code' name='apsispro-us-generated-code' value=''
								   readonly>
						</td>
					</table>
				</div>
			<?php
			else:
				echo '<p>' . __( 'You need to enter your APSIS Pro API Key in the setting for APSIS Pro for WP to use this plugin.', 'apsispro' ) . '</p>';
			endif;
			?>

		</form>
		<?php

	}

	/**
	 * Create/remove subscriber to/from mailing list at APSIS.
	 */
	public static function apsispro_action_us_callback() {

		$mode = isset( $_POST['mode'] ) ? $_POST['mode'] : '';

		if ( $mode === 'register' || $mode === 'default-sub' ) :

			$user_id = get_current_user_id();
			$email = ( isset( $_POST['email'] ) ? $_POST['email'] : '' );
			$name = ( isset( $_POST['name'] ) ? $_POST['name'] : '' );
			$skip = false;

			if ( $mode === 'register' ) :
				$mailinglist_id = ( isset( $_POST['listid'] ) ? $_POST['listid'] : '' );
			else :
				$options = get_option( 'apsispro_us_settings' );
				$user = new WP_User( $user_id );
				$role = array_shift($user -> roles);
				if ( $options['apsispro_us_select_role'] === $role ) :
					$mailinglist_id = $options['apsispro_us_select_default_subscriber'];
				else :
					$skip = true;
				endif;
			endif;

			if ( ! $skip ) :
				$response = self::create_update_apsispro_user( $user_id, $email, $name, $mailinglist_id );

				if ( is_wp_error( $response ) ):
					print( - 1 );
				else:
					print( $response['body'] );
				endif;
			endif;

			wp_die();

		elseif ( $mode === 'remove' ) :

			$listid = isset( $_POST['listid'] ) ? $_POST['listid'] : '';
			$email = isset( $_POST['email'] ) ? $_POST['email'] : '';
			$subscriber_id = self::get_subscribers_id( $email );

			$args    = array(
				'method' => 'DELETE',
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json'
				)
			);

			$options = get_option( 'apsispro_settings' );
			if ( isset( $options['apsispro_hidden_https'] ) ) :
				$https = $options['apsispro_hidden_https'];
			else :
				$https = false;
			endif;

			$response = wp_remote_request( APSIS_Pro_For_WP::get_api_url( $https, $options['apsispro_input_api_key'], $options['apsispro_select_api_url'] ) . '/v1/mailinglists/' . $listid . '/subscriptions/' . $subscriber_id, $args );

			if ( is_wp_error( $response ) ):
				print( - 1 );
			else:
				print( $response['body'] );
			endif;

		endif;

		wp_die();

	}

	/**
	 * Register shortcodes
	 */
	public static function register_shortcodes() {

		add_shortcode( 'apsispro_user_sub', array( __CLASS__, 'apsispro_shortcode' ) );

	}

	/**
	 * Shortcode for showing subscription form
	 */
	public static function apsispro_shortcode( $atts, $content = '' ) {

		$atts = shortcode_atts(
			array(
				'id'       => '',
				'text'     => false
			), $atts
		);

		$id_array = explode( ',', $atts['id'] );
		$text_array = explode( ',', $atts['text'] );
		$mailinglist_array = array_combine( $id_array, $text_array );

		ob_start();
		self::get_form( $mailinglist_array );
		$output = ob_get_clean();
		return $output;

	}

	/**
	 * Shows a subscription form for APSIS.
	 *
	 * @param int $mailinglist_id The id of the mailing list that user will be subscribed to
	 */
	public static function get_form( $mailinglist ) {

		if ( ! empty( $mailinglist ) && is_user_logged_in() ) : ?>
			<?php
			$current_user = wp_get_current_user();
			$email = $current_user->user_email;
			$name = $current_user->display_name;
			$subscriber_id = self::get_subscribers_id( $email );
			$subscriber_mailinglists = self::get_subscribers_mailinglists( $subscriber_id );
			?>
			<div class="apsispro-user-subscription">
				<form action="?apsispro_us_action" method="post" class="apsispro-user-subscription-form">
					<input type="hidden" name="apsispro-us-signup-name" class="apsispro-us-signup-name" value="<?php echo $name; ?>"/>
					<input type="hidden" name="apsispro-us-signup-email" class="apsispro-us-signup-email" value="<?php echo $email; ?>"/>
					<div class="apsispro-us-form-item apsispro-us-signup-mailinglists-item">
						<?php foreach ( $mailinglist as $mailinglist_item => $mailinglist_item_text ) {
							if ( $subscriber_mailinglists !== -1 ) :
								$subscriber_mailinglist_ids = array();
								foreach ( $subscriber_mailinglists as $subscriber_mailinglist_id ) {
									$subscriber_mailinglist_ids[] = $subscriber_mailinglist_id['Id'];
								}
								if ( in_array( $mailinglist_item, $subscriber_mailinglist_ids ) ) :
									$checked = 'checked="checked" ';
								else :
									$checked = '';
								endif;
							else :
								$checked = '';
							endif;
							echo '<p><input type="checkbox" id="apsispro-us-signup-mailinglists-' . $mailinglist_item . '" name="' . $mailinglist_item_text . '" class="apsispro-us-signup-mailinglists-id" ' . $checked . 'value="' . $mailinglist_item . '"><label for="apsispro-us-signup-mailinglists-' . $mailinglist_item . '">' . $mailinglist_item_text . '</label></p>';
						} ?>
					</div>
					<input type="hidden" name="apsispro-us-signup-thank-you" class="apsispro-us-signup-thank-you"
						   value=""/>
					<input type="submit" value="<?php _e( 'Subscribe', 'apsispro' ); ?>" name="apsispro-us-signup-button"
						   class="apsispro-us-signup-button">
				</form>
				<p class="apsispro-us-signup-response"></p>
			</div>
		<?php endif;

	}

	/**
	 * Get subscriber's id from APSIS Pro.
	 *
	 * @param String $email E-mail address of user
	 */
	public static function get_subscribers_id( $email ) {

		$subscriberEmail = '' . isset( $email ) ? $email : '';
		$args    = array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json'
			),
			'body'    => json_encode( $subscriberEmail )
		);

		$options = get_option( 'apsispro_settings' );
		if ( isset( $options['apsispro_hidden_https'] ) ) :
			$https = $options['apsispro_hidden_https'];
		else :
			$https = false;
		endif;

		$response = wp_remote_post( APSIS_Pro_For_WP::get_api_url( $https, $options['apsispro_input_api_key'], $options['apsispro_select_api_url'] ) . '/subscribers/v2/email', $args );

		if ( is_wp_error( $response ) ) :
			return -1;
		else :
			$response_array = json_decode( $response['body'], true );

			if ( 1 !== $response_array['Code'] ):
				return -1;
			else:
				return $response_array['Result'];
			endif;
		endif;

	}

	/**
	 * Get demographic data fields from APSIS Pro.
	 */
	public static function get_demographic_data_fields() {

		$args    = array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json'
			)
		);

		$options = get_option( 'apsispro_settings' );
		if ( isset( $options['apsispro_hidden_https'] ) ) :
			$https = $options['apsispro_hidden_https'];
		else :
			$https = false;
		endif;

		$response = wp_remote_get( APSIS_Pro_For_WP::get_api_url( $https, $options['apsispro_input_api_key'], $options['apsispro_select_api_url'] ) . '/accounts/v2/demographics', $args );

		if ( is_wp_error( $response ) ) :
			return -1;
		else :
			$response_array = json_decode( $response['body'], true );

			if ( 1 !== $response_array['Code'] ):
				return -1;
			else:
				$demo_data_fields = $response_array['Result']['Demographics'];
				$return_array = array();
				foreach ( $demo_data_fields as $demo_data_field ) :
					$return_array[] = $demo_data_field['Key'];
				endforeach;
				return $return_array;
			endif;
		endif;

	}

	/**
	 * Get all user meta data fields from WordPress and select the current option
	 *
	 * @param String $current_option Selected option in settings
	 */
	public static function get_user_meta_key( $current_option ) {
		global $wpdb;
		$select = "SELECT distinct $wpdb->usermeta.meta_key FROM $wpdb->usermeta";
		$usermeta = $wpdb->get_results($select);
		$return_data = '';
		if ( $current_option === '' || $current_option === 'none') :
			$return_data .= '<option value="none" selected="selected">' . __( 'None', 'apsispro' ) . '</option>';
		else :
			$return_data .= '<option value="none">' . __( 'None', 'apsispro' ) . '</option>';
		endif;
		foreach ( $usermeta as $usermeta_item ) :
			if ( $current_option === $usermeta_item->meta_key ) :
				$selected = ' selected="selected"';
			else:
				$selected = '';
			endif;
			$return_data .= '<option value="' . $usermeta_item->meta_key . '"' . $selected . '>' . $usermeta_item->meta_key . '</option>';
		endforeach;
		return $return_data;
	}

	/**
	 * Get subscriber's mailinglists from APSIS Pro.
	 *
	 * @param int $subscriberID Subscriber ID
	 */
	public static function get_subscribers_mailinglists( $subscriberID ) {

		$args    = array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json'
			)
		);

		$options = get_option( 'apsispro_settings' );
		if ( isset( $options['apsispro_hidden_https'] ) ) :
			$https = $options['apsispro_hidden_https'];
		else :
			$https = false;
		endif;

		$response = wp_remote_get( APSIS_Pro_For_WP::get_api_url( $https, $options['apsispro_input_api_key'], $options['apsispro_select_api_url'] ) . '/v1/subscribers/' . $subscriberID . '/mailinglists', $args );

		if ( is_wp_error( $response ) ) :
			return -1;
		else :
			$response_array = json_decode( $response['body'], true );

			if ( 1 !== $response_array['Code'] ):
				return -1;
			else:
				return $response_array['Result']['Mailinglists'];
			endif;
		endif;

	}

	/**
	 * 
	 *
	 * @param int $user_id WordPress user ID
	 */
	public static function apsispro_us_registration_save( $user_id ) {

		$user = new WP_User( $user_id );
		$role = array_shift($user -> roles);
		self::apsispro_us_registration_update_handler( $user_id, $role );

	}

	/**
	 * 
	 *
	 * @param int $user_id WordPress user ID
	 */
	public static function apsispro_us_profile_update( $user_id, $old_user_data ) {

		$user = new WP_User( $user_id );
		$role = array_shift($user -> roles);
		self::apsispro_us_registration_update_handler( $user_id, $role );

	}

	/**
	 * 
	 *
	 * @param int $user_id WordPress user ID
	 * @param String $role WordPress user role
	 */
	public static function apsispro_us_role_change( $user_id, $role ) {

		self::apsispro_us_registration_update_handler( $user_id, $role );

	}

	/**
	 * 
	 *
	 * @param int $user_id WordPress user ID
	 * @param String $role WordPress user role
	 */
	public static function apsispro_us_registration_update_handler( $user_id, $role ) {

		$current_user = get_user_by( 'id', $user_id );
		$email = $current_user->user_email;
		$name = $current_user->display_name;
		$subscriber_id = self::get_subscribers_id( $email );
		$subscriber_mailinglists = self::get_subscribers_mailinglists( $subscriber_id );
		$extra_options = get_option( 'apsispro_us_settings' );

		if( $subscriber_mailinglists !== -1) :
			foreach ( $subscriber_mailinglists as $mailinglist ) :
				$response = self::create_update_apsispro_user( $user_id, $email, $name, $mailinglist['Id'] );

				if ( is_wp_error( $response ) ):
					print( $response['body'] );
				endif;
			endforeach;
			if ( $extra_options['apsispro_us_select_role'] === $role ) :
				$default_subscriber_list = $extra_options['apsispro_us_select_default_subscriber'];

				if( ! empty( $extra_options['apsispro_us_select_default_subscriber'] ) ) :
					$response = self::create_update_apsispro_user( $user_id, $email, $name, $default_subscriber_list );

					if ( is_wp_error( $response ) ):
						print( $response['body'] );
					endif;
				endif;
			endif;
		else :
			if ( $extra_options['apsispro_us_select_default_general'] ) :
				$default_list = $extra_options['apsispro_us_select_default_general'];
				$response = self::create_update_apsispro_user( $user_id, $email, $name, $default_list );

				if ( is_wp_error( $response ) ):
					print( $response['body'] );
				endif;
			endif;
		endif;

	}

	/**
	 * Create/update user in APSIS Pro.
	 *
	 * @param int $user_id WordPress user ID
	 * @param String $email E-mail address of user
	 * @param String $name Name of user
	 * @param int $mailinglist_id Mailinglist ID
	 */
	public static function create_update_apsispro_user( $user_id, $email, $name, $mailinglist_id ) {

		$demo_options = get_option( 'apsispro_us_demo_settings' );
		$demo_array = array();
		foreach ( $demo_options as $demo_option => $value ) :
			$demo_array[] = array( 'Key' => $demo_option,
				'Value' => get_user_meta( $user_id, $value, true ) );
		endforeach;

		$form_data = array(
			'Email' 		=> $email,
			'Name'  		=> $name
		);
		if( $demo_array !== null || ! empty( $demo_array ) ) :
			$form_data['DemDataFields'] = $demo_array;
		endif;

		$args    = array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json'
			),
			'body'    => json_encode( $form_data
			)
		);
		$options = get_option( 'apsispro_settings' );
		if ( isset( $options['apsispro_hidden_https'] ) ) :
			$https = $options['apsispro_hidden_https'];
		else :
			$https = false;
		endif;

		$response = wp_remote_post( APSIS_Pro_For_WP::get_api_url( $https, $options['apsispro_input_api_key'], $options['apsispro_select_api_url'] ) . '/v1/subscribers/mailinglist/' . $mailinglist_id . '/create?updateIfExists=true', $args );

		return $response;

	}

}

APSIS_Pro_User_Subscriptions::init();