<?php
/**
 * This file contains only the Wikipedia class.
 *
 * @file
 * @package embed-wikimedia
 */

namespace Samwilson\EmbedWikimedia;

/**
 * Provide data and HTML for the Wikipedia embed.
 *
 * @package embed-wikimedia
 */
class Wikipedia extends WikimediaProject {

	/**
	 * {@inheritDoc}
	 */
	public function get_embed_url_pattern() {
		return '|https?://([a-z]+)\.wikipedia\.org/wiki/(.*)|i';
	}

	/**
	 * {@inheritDoc}
	 */
	public function embed( $matches, $attr, $url, $rawattr ) {
		return $this->html( $matches[2], [ 'lang' => $matches[1] ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function html( $title, $attrs = [] ) {
		$base_url = sprintf( 'https://%s.wikipedia.org', $attrs['lang'] );
		$rest_url = sprintf( '%s/api/rest_v1/page/summary/%s', $base_url, $title );
		$info     = $this->get_data( $rest_url );
		$img      = '';
		$url      = $info['content_urls']['desktop']['page'];
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

}
