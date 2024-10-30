<?php
/**
 * Admin Page Handler.
 *
 * @package BlockStyles
 * @since 1.0.0
 */

namespace wplemon\BlockStyles;

use Aristath\PayItForward;

/**
 * Admin Page Handler.
 *
 * @since 1.0.0
 */
class AdminPage {

	/**
	 * Init the admin page.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'wplemon_block_styles_admin_page', [ $this, 'settings_tab' ] );
		add_action( 'block_styles_settings_page_fields', [ $this, 'settings_fields' ], 5 );
		add_action( 'admin_init', [ $this, 'save_settings' ] );

		add_action(
			'wplemon_block_styles_admin_page',
			/**
			 * Add sponsors details.
			 *
			 * @access public
			 * @since 1.0.0
			 * @return void
			 */
			function() {
				require_once __DIR__ . '/PayItForward.php';
				$sponsors = new PayItForward();
				$sponsors->sponsors_details();
			},
			999
		);
	}

	/**
	 * Add the admin page.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_options_page(
			esc_html__( 'Block Styles', 'block-styles' ),
			esc_html__( 'Block Styles', 'block-styles' ),
			'manage_options',
			'block-styles',
			[ $this, 'page' ]
		);
	}

	/**
	 * The admin-page contents.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Block Styles', 'block-styles' ); ?></h1>

			<!-- Just adds some whitespace. -->
			<div style="height: 2em"></div>

			<div style="background: #fff; border: 1px solid #dedede; max-width: 50em; padding: 1em;">
				<p><?php esc_html_e( 'In the table below you can choose how styles will be added to your pages for each block.', 'block-styles' ); ?></p>
				<details>
					<summary><?php esc_html_e( 'What does "Enqueue In Head" do?', 'block-styles' ); ?></summary>
					<p><?php esc_html_e( 'Stylesheets will be added individually on the top of your page. You should only use this if you want a block-style to always be loaded, and be render-blocking (not recommended).', 'block-styles' ); ?></p>
				</details>
				<details>
					<summary><?php esc_html_e( 'What does "Enqueue In Footer" do?', 'block-styles' ); ?></summary>
					<p><?php esc_html_e( 'Stylesheets will be added individually on the bottom of your page. You should only use this if you want a block-style to always be loaded. Stylesheets loaded in the footer generally perform better that when in the head since they are not render-blocking.', 'block-styles' ); ?></p>
				</details>
				<details>
					<summary><?php esc_html_e( 'What does "Inline On Demand" do?', 'block-styles' ); ?></summary>
					<p><?php esc_html_e( 'Stylesheets will be added inline, only when a block is actually rendered on a page - regardless of where that block is located. This is the recommended method for most block styles and offers the best performance improvements for small block-styles.', 'block-styles' ); ?></p>
				</details>
				<details>
					<summary><?php esc_html_e( 'What does "Remove Styles" do?', 'block-styles' ); ?></summary>
					<p><?php esc_html_e( 'Stylesheets will not be added. Use this option if you don\'t use a block on your site, or if you want to add your own styles for it.', 'block-styles' ); ?></p>
				</details>
			</div>

			<?php do_action( 'wplemon_block_styles_admin_page' ); ?>
		</div>
		<?php
	}

	/**
	 * Print the settings tab.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_tab() {
		$styles = new \wplemon\BlockStyles\Styles();
		$blocks = $styles->get_styled_blocks();
		$values = get_option( 'block_styles_settings', [] );

		?>
		<form method="post">
			<table>
				<tbody>
					<?php
					foreach ( array_keys( $blocks ) as $id ) {
						$options   = [
							'enqueue-head'   => __( 'Enqueue In Head', 'block-styles' ),
							'enqueue-footer' => __( 'Enqueue In Footer', 'block-styles' ),
							'inline'         => __( 'Inline On Demand', 'block-styles' ),
							'disable'        => __( 'Remove Styles', 'block-styles' ),
						];
						$block_val = 'inline';
						if ( isset( $values[ $id ] ) && in_array( $values[ $id ], array_keys( $options ) ) ) {
							$block_val = $values[ $id ];
						}
						echo '<tr>';
						echo '<th style="padding:0.5em 1em;text-align:left;">' . esc_html( $id ) . '</th>';
						echo '<td>';
						foreach ( $options as $val => $label ) {
							$chosen = $val === $block_val ? 'checked' : '';
							echo '<input type="radio" name="block_styles_settings[' . esc_attr( $id ) . ']" id="block-styles-' . esc_attr( str_replace( '/', '-', $id ) ) . '-' . esc_attr( $val ) . '" ' . esc_html( $chosen ) . ' value="' . esc_attr( $val ) . '">';
							echo '<label for="block-styles-' . esc_attr( str_replace( '/', '-', $id ) ) . '-' . esc_attr( $val ) . '" style="padding: 1em;">' . esc_html( $label ) . '</label>';
						}
						echo '</td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
			<?php

			/**
			 * Add settings from hooks.
			 *
			 * @since 1.0.0
			 * @param array
			 */
			do_action( 'block_styles_settings_page_fields', $values );

			/**
			 * Add nonce field.
			 */
			wp_nonce_field( 'block-styles-settings' );
			?>

			<?php
			/**
			 * Add hidden input to denote the page - sanity check for save method.
			 */
			?>
			<input type="hidden" name="block-styles-settings" value="save">

			<?php
			/**
			 * The submit button.
			 */
			?>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Update Settings', 'block-styles' ); ?>"></p>
		<form>
		<?php
	}

	/**
	 * Save settings.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function save_settings() {

		// Sanity check.
		if ( ! isset( $_POST['block-styles-settings'] ) || 'save' !== $_POST['block-styles-settings'] ) {
			return;
		}

		// Security check:
		// Early exit if the current user doesn't have the correct permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Security check:
		// Early exit if nonce check fails.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'block-styles-settings' ) ) {
			return;
		}

		/**
		 * Build the value we're going to save.
		 */
		$save_value = [];
		if ( isset( $_POST['block_styles_settings'] ) ) {
			$save_value = [];
			foreach ( $_POST['block_styles_settings'] as $key => $val ) {
				$save_value[ $key ] = sanitize_text_field( $val );
			}
		}

		update_option( 'block_styles_settings', $save_value );
	}

	/**
	 * Add generic settings.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @param array $values An array of saved values.
	 *
	 * @return void
	 */
	public function settings_fields( $values ) {
		?>
		<?php
	}
}
