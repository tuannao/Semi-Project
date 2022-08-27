/* global tinymce */
tinymce.PluginManager.add('wpgallery', function( editor ) {

	function replaceGalleryShortcodes( content ) {
		return content.replace( /\[gallery([^\]]*)\]/g, function( match ) {
			return html( 'gallery', match );
		});
	}

	function html( cls, data ) {
		data = window.encodeURIComponent( data );
		return '<img src="' + tinymce.Env.transparentSrc + '" class="media mceItem ' + cls + '" ' +
			'data-media="' + data + '" data-mce-resize="false" data-mce-placeholder="1" alt="" />';
	}

	function restoreMediaShortcodes( content ) {
		function getAttr( str, name ) {
			name = new RegExp( name + '=\"([^\"]+)\"' ).exec( str );
			return name ? window.decodeURIComponent( name[1] ) : '';
		}

		return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
			var data = getAttr( image, 'data-media' );

			if ( data ) {
				return '<p>' + data + '</p>';
			}

			return match;
		});
	}

	function editMedia( node ) {
		var gallery, frame, data;

		if ( node.nodeName !== 'IMG' ) {
			return;
		}

		// Check if the `wp.media` API exists.
		if ( typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		data = window.decodeURIComponent( editor.dom.getAttrib( node, 'data-media' ) );

		// Make sure we've selected a gallery node.
		if ( editor.dom.hasClass( node, 'gallery' ) && wp.media.gallery ) {
			gallery = wp.media.gallery;
			frame = gallery.edit( data );

			frame.state('gallery-edit').on( 'update', function( selection ) {
				var shortcode = gallery.shortcode( selection ).string();
				editor.dom.setAttrib( node, 'data-media', window.encodeURIComponent( shortcode ) );
				frame.detach();
			});
		}
	}

	// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...').
	editor.addCommand( 'WP_Gallery', function() {
		editMedia( editor.selection.getNode() );
	});

	editor.on( 'mouseup', function( event ) {
		var dom = editor.dom,
			node = event.target;

		function unselect() {
			dom.removeClass( dom.select( 'img.media-selected' ), 'media-selected' );
		}

		if ( node.nodeName === 'IMG' && dom.getAttrib( node, 'data-media' ) ) {
			// Don't trigger on right-click.
			if ( event.button !== 2 ) {
				if ( dom.hasClass( node, 'media-selected' ) ) {
					editMedia( node );
				} else {
					unselect();
					dom.addClass( node, 'media-selected' );
				}
			}
		} else {
			unselect();
		}
	});

	// Display gallery, audio or video instead of img in the element path.
	editor.on( 'ResolveName', function( event ) {
		var dom = editor.dom,
			node = event.target;

		if ( node.nodeName === 'IMG' && dom.getAttrib( node, 'data-media' ) ) {
			if ( dom.hasClass( node, 'gallery' ) ) {
				event.name = 'gallery';
			}
		}
	});

	editor.on( 'BeforeSetContent', function( event ) {
		// 'wpview' handles the gallery shortcode when present.
		if ( ! editor.plugins.wpview || typeof wp === 'undefined' || ! wp.mce ) {
			event.content = replaceGalleryShortcodes( event.content );
		}
	});

	editor.on( 'PostProcess', function( event ) {
		if ( event.get ) {
			event.content = restoreMediaShortcodes( event.content );
		}
	});
});
