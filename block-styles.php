<?php
/**
 * Plugin Name:   Block Styles
 * Description:   Optimize the way your block styles get loaded.
 * Author:        Ari Stathopoulos (@aristath)
 * Author URI:    https://aristath.github.io
 * Version:       1.0.1
 * Text Domain:   block-styles
 * Requires WP:   5.2
 * Requires PHP:  5.6
 *
 * @package   block-styles
 * @author    Ari Stathopoulos (@aristath)
 * @copyright Copyright (c) 2020, Ari Stathopoulos (@aristath)
 * @license   https://opensource.org/licenses/MIT
 * @since     1.0.0
 */

define( 'BLOCK_STYLES_PLUGIN_FILE', __FILE__ );
define( 'BLOCK_STYLES_PLUGIN_DIR', __DIR__ );
define( 'BLOCK_STYLES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Init the plugin.
 */
function wplemon_block_styles() {
	require_once BLOCK_STYLES_PLUGIN_DIR . '/inc/Styles.php';
	require_once BLOCK_STYLES_PLUGIN_DIR . '/inc/AdminPage.php';
	if ( ! class_exists( 'Aristath\PayItForward' ) ) {
		require_once BLOCK_STYLES_PLUGIN_DIR . '/inc/PayItForward.php';
	}

	$styles = new \wplemon\BlockStyles\Styles();
	$styles->init();

	$admin_screen = new \wplemon\BlockStyles\AdminPage();
	$admin_screen->init();
}
wplemon_block_styles();
