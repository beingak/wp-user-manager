<?php
/**
 * Install function.
 *
 * @package     wp-user-manager
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Runs on plugin install by setting up the post types, custom taxonomies, flushing rewrite rules to initiate the new
 * slugs and also creates the plugin and populates the settings fields for those plugin pages.
 *
 * @param boolean $network_wide
 * @return void
 */
function wp_user_manager_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
			switch_to_blog( $blog_id );
			wpum_run_install();
			restore_current_blog();
		}
	} else {
		wpum_run_install();
	}

}

/**
 * Generates core pages and updates settings panel with the newly created pages.
 *
 * @since 1.0.0
 * @return void
 */
function wpum_generate_pages() {
	// Generate login page
	if ( ! wpum_get_option( 'login_page' ) ) {
		$login = wp_insert_post(
			array(
				'post_title'     => __( 'Login', 'wpum' ),
				'post_content'   => '[wpum_login_form psw_link="yes" register_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'login_page', [ $login ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'password_recovery_page' ) ) {
		$psw = wp_insert_post(
			array(
				'post_title'     => __( 'Password Reset', 'wpum' ),
				'post_content'   => '[wpum_password_recovery login_link="yes" register_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'password_recovery_page', [ $psw ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'registration_page' ) ) {
		$register = wp_insert_post(
			array(
				'post_title'     => __( 'Register', 'wpum' ),
				'post_content'   => '[wpum_register login_link="yes" psw_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'registration_page', [ $register ] );
	}
	// Generate account page
	if ( ! wpum_get_option( 'account_page' ) ) {
		$account = wp_insert_post(
			array(
				'post_title'     => __( 'Account', 'wpum' ),
				'post_content'   => '[wpum_account]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'account_page', [ $account ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'profile_page' ) ) {
		$profile = wp_insert_post(
			array(
				'post_title'     => __( 'Profile', 'wpum' ),
				'post_content'   => '[wpum_profile]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'profile_page', [ $profile ] );
	}
}

/**
 * Install the registration form into the database.
 *
 * @return void
 */
function wpum_install_registration_form() {

	$default_form_id = WPUM()->registration_forms->insert(
		[
			'name' => esc_html__( 'Default registration form' )
		]
	);

	$default_form = new WPUM_Registration_Form( $default_form_id );
	$default_form->add_meta( 'default', true );
	$default_form->add_meta( 'role', get_option( 'default_role' ) );
	$default_form->add_meta( 'fields', [] );

}

/**
 * Install emails into the database.
 *
 * @return void
 */
function wpum_install_emails() {

	$emails = [
		'registration_confirmation' => [
			'title'   => 'Welcome to {sitename}',
			'footer'  => '<a href="{siteurl}">{sitename}</a>',
			'content' => '<p>Hello {username}, and welcome to {sitename}. We’re thrilled to have you on board. </p>
<p>For reference, here\'s your login information:</p>
<p>Username: {username}<br />Login page: {login_page_url}<br />Password: {password}</p>
<p>Thanks,<br />{sitename}</p>',
			'subject' => 'Welcome to {sitename}'
		],

		'password_recovery_request' => [
			'subject' => 'Reset your {sitename} password',
			'title' => 'Reset your {sitename} password',
			'content' => '<p>Hello {username},</p>
<p>You are receiving this message because you or somebody else has attempted to reset your password on {sitename}.</p>
<p>If this was a mistake, just ignore this email and nothing will happen.</p>
<p>To reset your password, visit the following address:</p>
<p>{recovery_url}</p>',
			'footer' => '<a href="{siteurl}">{sitename}</a>'
		]
	];

	update_option( 'wpum_email', $emails );

}

/**
 * Run the installation process of the plugin.
 *
 * @return void
 */
function wpum_run_install() {

	// Enable registrations on the site.
	update_option( 'users_can_register', true );

	// Store plugin installation date.
	add_option( 'wpum_activation_date', strtotime( "now" ) );

	// Add Upgraded From Option.
	$current_version = get_option( 'wpum_version' );
	if ( $current_version ) {
		update_option( 'wpum_version_upgraded_from', $current_version );
	}

	// Install default pages
	wpum_generate_pages();

	// Add some default options.
	wpum_update_option( 'login_method', 'email' );
	wpum_update_option( 'lock_wplogin', true );
	wpum_update_option( 'email_template', 'default' );
	wpum_update_option( 'from_email', get_option( 'admin_email' ) );
	wpum_update_option( 'from_name', get_option( 'blogname' ) );
	wpum_update_option( 'guests_can_view_profiles', true );
	wpum_update_option( 'members_can_view_profiles', true );

	// Clear the permalinks.
	flush_rewrite_rules();

	// Setup permalinks for WPUM.
	update_option( 'wpum_permalink', 'username' );

	if ( ! $current_version ) {
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/upgrade-functions.php';

		// When new upgrade routines are added, mark them as complete on fresh install.
		$upgrade_routines = array(
			'v2_migration_options',
			'v2_migration_cover_field',
			'v2_migration_install_registration_form',
			'v2_migration_emails',
			'v2_install_search_fields',
			'v2_migrate_directories',
			'v2_migrate_fields',
			'v2_migrate_fields_groups'
		);
		foreach ( $upgrade_routines as $upgrade ) {
			wpum_set_upgrade_complete( $upgrade );
		}
	}

	// Update current version.
	update_option( 'wpum_version', WPUM_VERSION );

	// Add the transient to redirect.
	set_transient( '_wpum_activation_redirect', true, 30 );

}
