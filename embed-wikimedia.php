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
	exit( 1 );
}

wp_embed_register_handler( 'wikipedia', '|https?://([a-z]+\.wikipedia\.org)/wiki/(.*)|i', 'embed_wikimedia_wikipedia' );
wp_embed_register_handler( 'wikimedia_commons', '|https?://commons\.wikimedia\.org/wiki/(.*)|i', 'embed_wikimedia_commons' );
wp_embed_register_handler( 'wikidata', '|https?://(www\.)?wikidata\.org/wiki/(.*)|i', 'embed_wikimedia_wikidata' );

/**
 * Embed handler for Wikipedia URLs.
 *
 * @param string[] $matches Regex matches from the handler definition.
 * @param array    $attr Desired attributes of the returned image (can be ignored).
 * @param string   $url The requested URL.
 * @param string[] $rawattr The same as $attr but without argument parsing.
 *
 * @return string The HTML to embed.
 */
function embed_wikimedia_wikipedia( $matches, $attr, $url, $rawattr ) {
	$base_url      = $matches[1];
	$article_title = $matches[2];
	$rest_url      = sprintf( 'https://%s/api/rest_v1/page/summary/%s', $base_url, $article_title );
	$info          = embed_wikimedia_get_data( $rest_url );
	$img           = '';
	if ( isset( $info['thumbnail'] ) ) {
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

/**
 * Embed handler for Wikidata URLs.
 *
 * @param string[] $matches Regex matches from the handler definition.
 * @param array    $attr Desired attributes of the returned image (can be ignored).
 * @param string   $url The requested URL.
 * @param string[] $rawattr The same as $attr but without argument parsing.
 *
 * @return string The HTML to embed.
 */
function embed_wikimedia_commons( $matches, $attr, $url, $rawattr ) {
	$article_title = $matches[1];
	$rest_url      = sprintf( 'https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image=%s&thumbwidth=%s', $article_title, $attr['width'] );
	$info          = embed_wikimedia_get_data( $rest_url, 'xml' );
	$link_format   = '<a href="%s"><img src="%s" alt="%s" /></a>';
	$img_link      = sprintf( $link_format, $url, $info['file']['urls']['thumbnail'], $info['file']['name'] );
	$caption       = sprintf(
		'%1$s (%2$s) by %3$s, %4$s. %5$s',
		$info['file']['title'],
		$info['file']['date'],
		$info['file']['author'],
		$info['licenses']['license']['name'],
		$info['description']['language']
	);
	$caption_attrs = [
		'caption' => $caption,
		'width'   => $attr['width'],
		'align'   => 'aligncenter',
	];
	return img_caption_shortcode( $caption_attrs, $img_link );
}

/**
 * Embed handler for Wikidata URLs.
 *
 * @param string[] $matches Regex matches from the handler definition.
 * @param array    $attr Desired attributes of the returned image (can be ignored).
 * @param string   $url The requested URL.
 * @param string[] $rawattr The same as $attr but without argument parsing.
 *
 * @return string The HTML to embed.
 */
function embed_wikimedia_wikidata( $matches, $attr, $url, $rawattr ) {
	$item_id = $matches[2];
	$api_url = sprintf( 'https://www.wikidata.org/entity/%s', $item_id );
	$info    = embed_wikimedia_get_data( $api_url );
	$info    = $info['entities'][ $item_id ];

	// Label and description.
	$basic_info = embed_wikimedia_wikidata_basic_info( $info );
	$legend     = sprintf(
		'<strong><a href="%1$s"><img src="%2$s" alt="%3$s" /> %4$s</a>:</strong> %5$s',
		$url,
		plugin_dir_url( '' ) . 'embed-wikimedia/img/wikidata.png',
		__( 'Wikidata logo', 'embed-wikimedia' ),
		$basic_info['label'],
		$basic_info['description']
	);

	// Put it all together.
	$out = '<blockquote class="embed-wikimedia wikidata">' . $legend . '</blockquote>';
	return $out;
}

/**
 * Get the label and description of the given Wikidata item.
 *
 * @param array $info Info as returned by the API.
 * @return string[] With keys 'label' and 'description'.
 */
function embed_wikimedia_wikidata_basic_info( $info ) {
	$lang  = defined( 'WPLANG' ) ? WPLANG : 'en';
	$label = '';
	if ( isset( $info['labels'][ $lang ]['value'] ) ) {
		$label = $info['labels'][ $lang ]['value'];
	} elseif ( isset( $info['labels']['en']['value'] ) ) {
		$label = $info['labels']['en']['value'];
	}
	$description = '';
	if ( isset( $info['descriptions'][ $lang ]['value'] ) ) {
		$description = $info['descriptions'][ $lang ]['value'];
	} elseif ( isset( $info['descriptions']['en']['value'] ) ) {
		$description = $info['descriptions']['en']['value'];
	}
	return [
		'label'       => $label,
		'description' => $description,
	];
}

/**
 * Get the JSON data from an API call, caching for an hour if we're not in debug mode.
 *
 * @param string $url The URL to fetch.
 * @param string $response_format Either 'json' or 'xml'.
 *
 * @return mixed
 * @throws Exception If no data could be retrieved.
 */
function embed_wikimedia_get_data( $url, $response_format = 'json' ) {
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
