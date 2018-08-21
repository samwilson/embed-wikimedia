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
 * Version:           0.1.0
 * Author:            Sam Wilson
 * Author URI:        https://samwilson.id.au
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       embed-wikimedia
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	exit(1);
}

wp_embed_register_handler( 'wikipedia', "|https?://([a-z]+\.wikipedia\.org)/wiki/(.*)|i",
	function ( $matches, $attr, $url, $rawattr ) {
		$base_url      = $matches[1];
		$article_title = $matches[2];
		$rest_url      = sprintf( 'https://%s/api/rest_v1/page/summary/%s', $base_url, $article_title );
		$info          = embed_wikimedia_get_data( $rest_url );
		$img           = '';
		if (isset($info['thumbnail'])) {
			$img = sprintf(
				'<a href="%1$s"><img src="%2$s" alt="%3$s" width="%4$s" height="%5$s" /></a>',
				$url,
				$info['thumbnail']['source'],
				$info['description'],
				$info['thumbnail']['width'],
				$info['thumbnail']['height']
			);
		}
		$out = '<blockquote class="embed-wikimedia">'
			. '<a href="' . $url . '"><strong>' . $info['displaytitle'] . '</strong></a>'
			. $img
			. $info['extract_html']
			. '</blockquote>';
		return $out;
	}
);

/**
 * Get the JSON data from an API call, caching for an hour if we're not in debug mode.
 *
 * @param $url
 *
 * @return mixed
 * @throws Exception
 */
function embed_wikimedia_get_data( $url ) {
	$transient_name = 'embed_wikimedia_url_' . md5( $url );
	$cached         = get_transient( $transient_name );
	if ( $cached && ! WP_DEBUG ) {
		return $cached;
	}
	$response = wp_remote_get( $url );
	if ( $response instanceof WP_Error ) {
		// translators: error message displayed when no response could be got from an API call.
		$msg = __( 'Unable to retrieve URL: %s', 'embed-wikimedia' );
		throw new Exception( sprintf( $msg, $url ) );
	} else {
		$info = json_decode( $response['body'], true );
		set_transient( $transient_name, $info, 60 * 60 );
		return $info;
	}
}
