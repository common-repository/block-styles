<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Print block styles.
 *
 * @package block-styles
 * @since 1.0.0
 */

namespace wplemon\BlockStyles;

/**
 * Template handler.
 *
 * @since 1.0.0
 */
class Styles {

	/**
	 * An array of all blocks styles.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var array
	 */
	protected $block_styles;

	/**
	 * Whether the core styles are enqueued or not.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var bool
	 */
	protected $has_core_styles = false;

	/**
	 * An array of blocks used in this page.
	 *
	 * @static
	 * @access private
	 * @since 1.0.0
	 * @var array
	 */
	private static $block_styles_added = [];

	/**
	 * A string containing all blocks styles
	 *
	 * @static
	 * @access private
	 * @since 1.0.0
	 * @var string
	 */
	private static $footer_block_styles = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 999 );

		/**
		 * Use a filter to figure out which blocks are used.
		 * We'll use this to populate the $blocks property of this object
		 * and enque the CSS needed for them.
		 */
		add_filter( 'render_block', [ $this, 'add_inline_styles' ], 10, 2 );

		/**
		 * Add admin styles for blocks.
		 */
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );

		/**
		 * Add some styles in the footer.
		 */
		add_action( 'wp_footer', [ $this, 'footer_styles' ] );
	}

	/**
	 * Get an array of block styles.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array
	 */
	public function get_styled_blocks() {

		// Only run once.
		if ( $this->block_styles ) {
			return $this->block_styles;
		}

		$this->block_styles = [];

		$default_folders = is_admin() || $this->has_core_styles ? [
			[
				'namespace' => 'core',
				'path'      => trailingslashit( BLOCK_STYLES_PLUGIN_DIR ) . 'assets/css/',
				'url'       => trailingslashit( BLOCK_STYLES_PLUGIN_URL ) . 'assets/css/',
			],
		] : [];

		/**
		 * Filter folders containing block styles.
		 *
		 * @since 1.0.0
		 * @param array $folders An array of folders.
		 *                       [
		 *                           namespace => 'core',
		 *                           path      => absolute_path_slashed,
		 *                           url       => url_shashed
		 *                       ]
		 */

		$folders = apply_filters( 'wplemon_block_styles_folders', $default_folders );

		foreach ( $folders as $folder ) {

			// Iterate and find files in this folder.
			$iterator = new \DirectoryIterator( $folder['path'] );
			foreach ( $iterator as $file_info ) {

				// Skip dot files.
				if ( $file_info->isDot() ) {
					continue;
				}

				// The block name.
				$block_name = pathinfo( $file_info->getFilename() )['filename'];

				// Skip the file if empty.
				if ( 0 === filesize( $file_info->getPathname() ) ) {
					continue;
				}

				// Add the stylesheet to our array.
				if ( ! isset( $this->block_styles[ "{$folder['namespace']}/{$block_name}" ] ) ) {
					$this->block_styles[ "{$folder['namespace']}/{$block_name}" ] = [];
				}
				$this->block_styles[ "{$folder['namespace']}/{$block_name}" ][] = [
					'path' => $file_info->getPathname(),
					'url'  => $folder['url'] . $file_info->getFilename(),
				];
			}
		}

		/**
		 * Filter files for blocks.
		 *
		 * @since 1.0.0
		 * @param array $block_styles An array of all our block styles.
		 */
		return apply_filters( 'wplemon_block_styles', $this->block_styles );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function scripts() {

		if ( is_admin() || in_array( 'wp-block-library', wp_styles()->queue, true ) || in_array( 'wp-block-library-theme', wp_styles()->queue, true ) ) {
			$this->has_core_styles = true;
		}

		// Dequeue wp-core blocks styles. These will be added depending on our options.
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );

		// Enqueue blocks in head.
		$enqueue_head = $this->get_blocks_by_styling_method( 'enqueue-head' );
		if ( ! empty( $enqueue_head ) ) {
			$blocks = $this->get_styled_blocks();
			foreach ( $enqueue_head as $id ) {
				if ( isset( $blocks[ $id ] ) ) {
					foreach ( $blocks[ $id ] as $style ) {
						wp_enqueue_style( 'wplemon-block-style-' . sanitize_key( $id ), $style['url'], [], filemtime( $style['path'] ) );
					}
				}
			}
		}

		// Enqueue blocks in footer.
		add_action(
			'wp_footer',
			function() {
				$enqueue_footer = $this->get_blocks_by_styling_method( 'enqueue-footer' );
				if ( ! empty( $enqueue_footer ) ) {
					$blocks = $this->get_styled_blocks();
					foreach ( $enqueue_footer as $id ) {
						if ( isset( $blocks[ $id ] ) ) {
							foreach ( $blocks[ $id ] as $style ) {
								wp_enqueue_style( 'wplemon-block-style-' . sanitize_key( $id ), $style['url'], [], filemtime( $style['path'] ) );
							}
						}
					}
				}
			}
		);
	}

	/**
	 * Get blocks array depending on method of styling.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $method The styling method.
	 * @return array
	 */
	public function get_blocks_by_styling_method( $method ) {
		$option = get_option( 'block_styles_settings', [] );
		$blocks = $this->get_styled_blocks();
		$result = [];

		foreach ( $blocks as $key => $val ) {
			if ( ! isset( $option[ $key ] ) ) {
				$option[ $key ] = 'inline';
			}
			if ( $method === $option[ $key ] ) {
				$result[] = $key;
			}
		}
		return $result;
	}

	/**
	 * Filters the content of a single block.
	 *
	 * Adds inline styles to blocks. Styles will only be added the 1st time we encounter the block.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string               Returns $block_content with our modifications.
	 */
	public function add_inline_styles( $block_content, $block ) {
		$blocks_styles = $this->get_styled_blocks();
		$inline        = $this->get_blocks_by_styling_method( 'inline' );

		if ( $block['blockName'] ) {
			if ( ! in_array( $block['blockName'], self::$block_styles_added, true ) ) {
				self::$block_styles_added[] = $block['blockName'];

				if ( in_array( $block['blockName'], $inline, true ) && isset( $blocks_styles[ $block['blockName'] ] ) ) {
					ob_start();
					foreach ( $blocks_styles[ $block['blockName'] ] as $block_styles ) {
						include $block_styles['path'];
					}
					self::$footer_block_styles .= ob_get_clean();
				}
			}
		}
		return $block_content;
	}

	/**
	 * Enqueue block assets.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_block_assets() {

		// We only need this in the editor.
		if ( ! is_admin() ) {
			return;
		}

		// Get an array of blocks.
		$blocks = $this->get_styled_blocks();

		foreach ( $blocks as $blocks_styles ) {

			// Add blocks styles.
			foreach ( $blocks_styles as $block => $block_style ) {
				wp_enqueue_style(
					'wplemon-block-style-' . $block . md5( $block_style['url'] ),
					$block_style['url'],
					[],
					filemtime( $block_style['path'] )
				);
			}
		}
	}

	/**
	 * Print styles in the footer.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function footer_styles() {
		/**
		 * Note to reviewers: This is pure CSS coming from CSS files.
		 *
		 * There is no need to escape it, wp_strip_all_tags() is enough to make sure
		 * that the browser interprets everything as a style and avoid injections.
		 */
		echo '<style id="wplemon-block-styles">' . wp_strip_all_tags( self::$footer_block_styles ) . '</style>';

		// Make sure any styles that were appended to core styles also get appended.
		wp_styles()->print_inline_style( 'wp-block-library' );
		wp_styles()->print_inline_style( 'wp-block-library-theme' );
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
