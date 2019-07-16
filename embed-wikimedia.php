<?php
/**
 * The Embed Wikimedia plugin adds support for embedding links to Wikimedia projects such as Wikipedia.
 *
 * @file
 * @package           embed-wikimedia
 * @since             0.1.0
 *
 * @wordpress-plugin
 * Plugin Name:       Embed Wikimedia
 * Plugin URI:
 * Description:       Embed links to Wikimedia projects such as Wikipedia.
 * Version:           0.2.0
 * Author:            Sam Wilson
 * Author URI:        https://samwilson.id.au
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       embed-wikimedia
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	exit( 1 );
}

// Make sure Composer has been set up (for installation from Git, mostly).
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		function() {
			$msg = 'The Embed Wikimedia plugin is not fully installed. Please run <kbd>composer install</kbd> in its directory.';
			// phpcs:ignore
			echo "<div class='error'><p>" . __( $msg, 'embed-wikimedia') . "</p></div>";
		}
	);
	return;
}
require __DIR__ . '/vendor/autoload.php';

add_action(
	'init',
	function () {
		// Register the script that'll handle all blocks.
		$script_url    = plugins_url( 'resources/blocks.js', __FILE__ );
		$script_handle = 'embed-wikimedia';
		wp_register_script(
			$script_handle,
			$script_url,
			array( 'wp-blocks', 'wp-element', 'wp-api' ),
			1,
			true
		);

		// Register the blocks and embed URLs.
		$sites = [ 'commons', 'wikipedia', 'wikidata' ];
		foreach ( $sites as $site ) {
			$site_class = 'Samwilson\\EmbedWikimedia\\' . ucfirst( $site );
			$site_obj   = new $site_class();

			// Block.
			register_block_type( "embed-wikimedia/$site", array( 'editor_script' => $script_handle ) );

			// The embed URL.
			wp_embed_register_handler( 'embed-wikimedia-' . $site, $site_obj->get_embed_url_pattern(), [ $site_obj, 'embed' ] );

			// API endpoint.
			add_action(
				'rest_api_init',
				function () use ( $site, $site_obj ) {
					register_rest_route(
						'embed-wikimedia/v1',
						"/$site/(?P<title>.*+)",
						array(
							'methods'  => 'GET',
							'callback' => function ( WP_REST_Request $request ) use ( $site_obj ) {
								$html = $site_obj->html( $request->get_param( 'title' ), $request->get_params() );
								return array( 'embed_html' => $html );
							},
						)
					);
				}
			);
		}
	}
);
