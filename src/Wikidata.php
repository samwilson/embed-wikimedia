<?php
/**
 * This file contains only the Wikidata class.
 *
 * @file
 * @package embed-wikimedia
 */

namespace Samwilson\EmbedWikimedia;

/**
 * Provide data and HTML for the Wikidata embed.
 *
 * @package embed-wikimedia
 */
class Wikidata extends WikimediaProject {

	/**
	 * {@inheritDoc}
	 */
	public function get_embed_url_pattern() {
		return '|https?://(www\.)?wikidata\.org/wiki/(.*)|i';
	}

	/**
	 * {@inheritDoc}
	 */
	public function embed( $matches, $attr, $url, $rawattr ) {
		return $this->html( $matches[2] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function html( $title, $attrs = [] ) {
		if ( substr( $title, 0, 1 ) !== 'Q' ) {
			$title = "Q$title";
		}
		$api_url = sprintf( 'https://www.wikidata.org/entity/%s', $title );
		$info    = $this->get_data( $api_url );
		$info    = $info['entities'][ $title ];

		// Label and description.
		$basic_info = $this->extract_basic_info( $info );
		$legend     = sprintf(
			'<strong><a href="%1$s"><img src="%2$s" alt="%3$s" /> %4$s</a>:</strong> %5$s',
			'https://www.wikidata.org/wiki/' . $title,
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
	protected function extract_basic_info( $info ) {
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
}
