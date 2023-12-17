<?php
/**
 * Manages CoCart dashboard assets.
 *
 * @author   Sébastien Dumont
 * @package  CoCart\Admin
 * @since    1.2.0
 * @version  3.0.17
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Admin_Assets' ) ) {

	class CoCart_Admin_Assets {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			// Registers and enqueue Stylesheets.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

			// Adds admin body classes.
			add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		} // END __construct()

		/**
		 * Registers and enqueue Stylesheets.
		 *
		 * @access public
		 *
		 * @since 1.2.0 Introduced.
		 * @since 3.10.0 Use of $hook_suffix parameter was added instead of using the current screen.
		 *
		 * @param string $hook_suffix The current admin page.
		 */
		public function admin_styles( $hook_suffix ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( strpos( $hook_suffix, 'cocart' ) !== false || ( isset( $_GET['page'] ) && strpos( trim( sanitize_key( wp_unslash( $_GET['page'] ) ) ), 'cocart' ) === 0 ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_register_style( COCART_SLUG . '_admin', COCART_URL_PATH . '/assets/css/admin/cocart' . $suffix . '.css', array(), COCART_VERSION );
				wp_enqueue_style( COCART_SLUG . '_admin' );
				wp_style_add_data( COCART_SLUG . '_admin', 'rtl', 'replace' );
				if ( $suffix ) {
					wp_style_add_data( COCART_SLUG . '_admin', 'suffix', '.min' );
				}
			}
			if ( $suffix ) {
				wp_style_add_data( COCART_SLUG . '_admin', 'suffix', '.min' );
			}
		} // END admin_styles()

		/**
		 * Adds admin body class for CoCart page.
		 *
		 * @access public
		 *
		 * @since   1.2.0 Introduced.
		 * @version 3.0.7
		 *
		 * @param string $classes Classes already registered.
		 *
		 * @return string $classes All classes registered.
		 */
		public function admin_body_class( $classes ) {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Add body class for CoCart page.
			if ( 'toplevel_page_cocart' === $screen_id || 'toplevel_page_cocart-network' === $screen_id ) {
				$classes = ' cocart ';
			}

			// Add special body class for plugin install page.
			if ( 'plugin-install' === $screen_id || 'plugin-install-network' === $screen_id ) {
				if ( isset( $_GET['tab'] ) && 'cocart' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$classes = ' cocart-plugin-install ';
				}
			}

			return $classes;
		} // END admin_body_class()

	} // END class

} // END if class exists

return new CoCart_Admin_Assets();
