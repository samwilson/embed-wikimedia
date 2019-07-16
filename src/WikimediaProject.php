<?php
/**
 * This file contains only the WikimediaProject class, from which all project classes inherit.
 *
 * @file
 * @package embed-wikimedia
 */

namespace Samwilson\EmbedWikimedia;

use Exception;
use SimpleXMLElement;
use WP_Error;

/**
 * Parent class for all individual projects.
 *
 * @package embed-wikimedia
 */
abstract class WikimediaProject {

	/**
	 * Get the embed URL pattern.
	 *
	 * @return string The regex that will be used to see if this handler should be used for a URL.
	 */
	abstract public function get_embed_url_pattern();

	/**
	 * Embed handler for wiki URLs.
	 *
	 * @param string[] $matches Regex matches from the handler definition.
	 * @param array    $attr Desired attributes of the returned image (can be ignored).
	 * @param string   $url The requested URL.
	 * @param string[] $rawattr The same as $attr but without argument parsing.
	 *
	 * @return string The HTML to embed.
	 */
	abstract public function embed( $matches, $attr, $url, $rawattr );

	/**
	 * Get the HTML to be embedded for a given title.
	 *
	 * @param string $title The page title.
	 * @param array  $attrs Attributes such as 'width', 'lang' etc.
	 *
	 * @return mixed
	 */
	abstract public function html( $title, $attrs = [] );

	/**
	 * Get the JSON data from an API call, caching for an hour if we're not in debug mode.
	 *
	 * @param string $url The URL to fetch.
	 * @param string $response_format Either 'json' or 'xml'.
	 *
	 * @return mixed
	 * @throws Exception If no data could be retrieved.
	 */
	protected function get_data( $url, $response_format = 'json' ) {
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
		}
		$info = ( 'xml' === $response_format )
			? json_decode( wp_json_encode( new SimpleXMLElement( $response['body'] ) ), true )
			: json_decode( $response['body'], true );
		set_transient( $transient_name, $info, 60 * 60 );
		return $info;
	}
}
