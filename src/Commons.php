<?php
/**
 * This file contains only the Commons class.
 *
 * @file
 * @package embed-wikimedia
 */

namespace Samwilson\EmbedWikimedia;

use Exception;

/**
 * Provide data and HTML for the Wikimedia Commons embed.
 *
 * @package embed-wikimedia
 */
class Commons extends WikimediaProject {

	/**
	 * {@inheritDoc}
	 */
	public function get_embed_url_pattern() {
		return '|https?://commons\.wikimedia\.org/wiki/(.*)|i';
	}

	/**
	 * {@inheritDoc}
	 */
	public function embed( $matches, $attr, $url, $rawattr ) {
		return $this->html( $matches[1], [ 'width' => $attr['width'] ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $title The file title from the URL, with 'File:' at the beginning.
	 */
	public function html( $title, $attrs = [] ) {
		// Get basic file information from Commons.
		$info_url_format   = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=info&inprop=url&titles=%s';
		$info_url          = sprintf( $info_url_format, str_replace( ' ', '_', wp_unslash( $title ) ) );
		$image_info_result = $this->get_data( $info_url );
		if ( ! isset( $image_info_result['query'] ) || ! isset( $image_info_result['query']['pages'] ) ) {
			// translators: Error message shown when unable to retrieve Commons API data.
			$msg_format = __( 'Unable to fetch file information for: %s', 'embed-wikimedia' );
			return '<p class="error">' . sprintf( $msg_format, $title ) . '</p>';
		}
		$image_info = array_shift( $image_info_result['query']['pages'] );
		$file_url   = $image_info['canonicalurl'];
		$file_title = substr( wp_unslash( $image_info['title'] ), strlen( 'File:' ) );
		$file_name  = str_replace( ' ', '_', $file_title );

		// First get data from the commonsapi tool. The 'image' param must not have a File prefix.
		$url_format = 'https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image=%s&thumbwidth=%s';
		$rest_url   = sprintf( $url_format, $file_name, $attrs['width'] );

		try {
			$info = $this->get_data( $rest_url, 'xml' );
		} catch ( Exception $exception ) {
			return '<p class="error">' . $exception->getMessage() . '</p>';
		}
		if ( isset( $info['error'] ) ) {
			return '<p class="error">' . $info['error'] . '</p>';
		}

		// Then see if there's a caption.
		$media_url_pattern = 'https://commons.wikimedia.org/w/api.php?action=wbgetentities&format=json&ids=%s';
		$media_id          = 'M' . $image_info['pageid'];
		$media_info_result = $this->get_data( sprintf( $media_url_pattern, $media_id ) );
		$description       = '';
		if ( isset( $media_info_result['entities'] ) && count( $media_info_result['entities'] ) >= 1 ) {
			$entity = array_shift( $media_info_result['entities'] );
			// @TODO Handle language fallbacks more correctly.
			$lang_code      = get_bloginfo( 'language' );
			$base_lang_code = substr( $lang_code, 0, strpos( $lang_code, '-' ) );
			$lang_codes     = [ $lang_code, $base_lang_code, 'en' ];
			foreach ( $lang_codes as $code ) {
				if ( isset( $entity['labels'][ $code ]['value'] ) ) {
					$description = $entity['labels'][ $code ]['value'];
					break;
				}
			}
		}
		if ( '' === $description
			&& isset( $info['description']['language'] ) && ! is_array( $info['description']['language'] )
		) {
			$description = $info['description']['language'];
		}

		// Put it all together.
		$link_format = '<a href="%s"><img src="%s" alt="%s" /></a>';
		$img_link    = sprintf( $link_format, $file_url, $info['file']['urls']['thumbnail'], $file_name );
		$date        = isset( $info['file']['date'] ) && ! is_array( $info['file']['date'] ) ? $info['file']['date'] : '';
		$author      = isset( $info['file']['author'] ) ? $info['file']['author'] : '';
		$license     = isset( $info['licenses']['license'][0] ) ? $info['licenses']['license'][0] : $info['licenses']['license'];
		$caption     = sprintf(
			// translators: Format for the Commons image caption. 1: caption; 2: Commons URL; 3: Commons file title; 4: date; 5: author; 6: license code.
			__( '%1$s &mdash; <a href="%2$s">%3$s</a> (%4$s) by %5$s, %6$s.', 'embed-wikimedia' ),
			$description,
			$file_url,
			$file_title,
			$date,
			$author,
			$license['name']
		);
		$caption_attrs = [
			'caption' => $caption,
			'width'   => $attrs['width'],
			'align'   => 'aligncenter',
		];
		return img_caption_shortcode( $caption_attrs, $img_link );
	}
}
