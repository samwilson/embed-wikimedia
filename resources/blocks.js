/**
 * Define all block-editor blocks for this plugin.
 *
 * @package embed-wikimedia
 * @file
 */

( function( blocks, element, i18n, api ) {

	var fetchEmbedHtml = function ( apiUrl, props ) {
		window.wp.apiFetch( {path: apiUrl} ).then(
			function (response) {
				if (response.error !== undefined) {
					return;
				}
				props.setAttributes( { embed_html: response.embed_html } );
			}
		)
	};

	/**
	 * 1 of 3: Commons.
	 */
	blocks.registerBlockType(
		'embed-wikimedia/commons',
		{
			title: i18n.__( 'Wikimedia Commons' ),
			description: i18n.__( "Embed a file from Wikimedia Commons." ),
			keywords: [ "image", "media", "file" ],
			icon: 'format-image',
			category: 'embed',
			attributes: {
				filename: { type: "string" },
				embed_html: { type: "string" }
			},
			edit: editCommons,
			save: function ( props ) {
				// Standard embed syntax (plain URL on its own line).
				return 'https://commons.wikimedia.org/wiki/File:' + encodeURI( props.attributes.filename );
			}
		}
	);
	function editCommons( props ) {
		var filename_input       = element.createElement(
			wp.components.TextControl,
			{
				label: i18n.__( 'Wikimedia Commons file name:' ),
				value: props.attributes.filename,
				onChange: function ( newFilename ) {
					props.setAttributes( {filename: newFilename.trim(), embed_html: ''} );
				}
			}
		);
		var embed_preview_button = element.createElement(
			wp.components.Button,
			{
				isDefault: true,
				onClick: function () {
					props.setAttributes( { embed_html: i18n.__( 'Loading...' ) } );
					var apiUrl = '/embed-wikimedia/v1/commons/' + encodeURI( props.attributes.filename ) + '?width=700';
					fetchEmbedHtml( apiUrl, props );
				}
			},
			i18n.__( 'Preview' )
		);
		var embed_html           = element.createElement( element.RawHTML, {}, props.attributes.embed_html );
		return element.createElement( "div", {}, filename_input, embed_preview_button, embed_html );
	}

	/**
	 * 2 of 3: Wikipedia.
	 */
	blocks.registerBlockType(
		'embed-wikimedia/wikipedia',
		{
			title: 'Wikipedia',
			description: i18n.__( 'Embed a Wikipedia article.' ),
			keywords: [ 'encyclopedia' ],
			icon: 'admin-site-alt2',
			category: 'embed',
			attributes: {
				wiki_lang: { type: "string" },
				wiki_title: { type: "string" },
				embed_html: { type: "string" }
			},
			edit: editWikipedia,
			save: function ( props ) {
				// Standard embed syntax (plain URL on its own line).
				return 'https://' + props.attributes.wiki_lang + '.wikipedia.org/wiki/' + encodeURI( props.attributes.wiki_title );
			}
		}
	);
	function editWikipedia( props ) {
		var embed_preview_button = element.createElement(
			wp.components.Button,
			{
				isDefault: true,
				onClick: function () {
					props.setAttributes( { embed_html: i18n.__( 'Loading...' ) } );
					var apiUrl = '/embed-wikimedia/v1/wikipedia/' + props.attributes.wiki_title + '?lang=' + props.attributes.wiki_lang;
					fetchEmbedHtml( apiUrl, props );
				}
			},
			i18n.__( 'Preview' )
		);
		var wiki_lang_input      = element.createElement(
			wp.components.TextControl,
			{
				label: i18n.__( 'Wikipedia language code:' ),
				value: props.attributes.wiki_lang,
				onChange: function ( newLang ) {
					props.setAttributes( { embed_html: '', wiki_lang: newLang.trim() } );
				}
			}
		);
		var wiki_title_input     = element.createElement(
			wp.components.TextControl,
			{
				label: i18n.__( 'Article title:' ),
				value: props.attributes.wiki_title,
				onChange: function ( newTitle ) {
					props.setAttributes( { embed_html: '', wiki_title: newTitle.trim() } );
				}
			}
		);
		var embed_html           = element.createElement( element.RawHTML, {}, props.attributes.embed_html );
		return element.createElement( 'div', {}, wiki_lang_input, wiki_title_input, embed_preview_button, embed_html );
	}

	/**
	 * 3 of 3: Wikidata.
	 */
	blocks.registerBlockType(
		'embed-wikimedia/wikidata',
		{
			title: 'Wikidata',
			description: i18n.__( "Embed a Wikidata item." ),
			keywords: [ "" ],
			icon: 'editor-table',
			category: 'embed',
			attributes: {
				wikidata_item: { type: "string" },
				embed_html: { type: "string" }
			},
			edit: editWikidata,
			save: function ( props ) {
				// Standard embed syntax (plain URL on its own line).
				return 'https://www.wikidata.org/wiki/' + props.attributes.wikidata_item;
			}
		}
	);
	function editWikidata( props ) {
		var embed_html           = element.createElement( element.RawHTML, {}, props.attributes.embed_html );
		var item_id_input        = element.createElement(
			wp.components.TextControl,
			{
				label: i18n.__( 'Wikidata item ID:' ),
				value: props.attributes.wikidata_item,
				onChange: function ( newItemId ) {
					props.setAttributes( { embed_html: '', wikidata_item: newItemId.trim() } );
				}
			}
		);
		var embed_preview_button = element.createElement(
			wp.components.Button,
			{
				isDefault: true,
				onClick: function () {
					props.setAttributes( { embed_html: i18n.__( 'Loading...' ) } );
					var apiUrl = '/embed-wikimedia/v1/wikidata/' + encodeURI( props.attributes.wikidata_item );
					fetchEmbedHtml( apiUrl, props );
				}
			},
			i18n.__( 'Preview' )
		);
		return element.createElement( "div", {}, item_id_input, embed_preview_button, embed_html );
	}

}( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.api ) );
