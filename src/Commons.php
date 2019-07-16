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
	 */
	public function html( $title, $attrs = [] ) {
		$url_format = 'https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image=%s&thumbwidth=%s';
		$rest_url   = sprintf( $url_format, $title, $attrs['width'] );
		try {
			$info = $this->get_data( $rest_url, 'xml' );
		} catch ( Exception $exception ) {
			return '<p class="error">' . $exception->getMessage() . '</p>';
		}
		$link_format = '<a href="%s"><img src="%s" alt="%s" /></a>';
		if ( isset( $info['error'] ) ) {
			return '<p class="error">' . $info['error'] . '</p>';
		}
		$url           = 'https://commons.wikimedia.org/wiki/File:' . $title;
		$img_link      = sprintf( $link_format, $url, $info['file']['urls']['thumbnail'], $info['file']['name'] );
		$license       = isset( $info['licenses']['license'][0] ) ? $info['licenses']['license'][0] : $info['licenses']['license'];
		$date          = is_string( $info['file']['date'] ) ? $info['file']['date'] : '';
		$caption       = sprintf(
			'%1$s (%2$s) by %3$s, %4$s. %5$s',
			$info['file']['title'],
			$date,
			$info['file']['author'],
			$license['name'],
			$info['description']['language']
		);
		$caption_attrs = [
			'caption' => $caption,
			'width'   => $attrs['width'],
			'align'   => 'aligncenter',
		];
		return img_caption_shortcode( $caption_attrs, $img_link );
	}
}
