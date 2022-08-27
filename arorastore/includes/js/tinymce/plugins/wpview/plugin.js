/**
 *  View plugin.
 */
( function( tinymce ) {
	tinymce.PluginManager.add( 'view', function( editor ) {
		function noop () {}

		// Set this here as tinymce.js may be loaded too early.
		var  = window.;

		if ( !  || ! .mce || ! .mce.views ) {
			return {
				getView: noop
			};
		}

		// Check if a node is a view or not.
		function isView( node ) {
			return editor.dom.hasClass( node, 'view' );
		}

		// Replace view tags with their text.
		function resetViews( content ) {
			function callback( match, $1 ) {
				return '<p>' + window.decodeURIComponent( $1 ) + '</p>';
			}

			if ( ! content || content.indexOf( ' data-view-' ) === -1 ) {
				return content;
			}

			return content
				.replace( /<div[^>]+data-view-text="([^"]+)"[^>]*>(?:\.|[\s\S]+?view-end[^>]+>\s*<\/span>\s*)?<\/div>/g, callback )
				.replace( /<p[^>]+data-view-marker="([^"]+)"[^>]*>[\s\S]*?<\/p>/g, callback );
		}

		editor.on( 'init', function() {
			var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

			if ( MutationObserver ) {
				new MutationObserver( function() {
					editor.fire( 'body-class-change' );
				} )
				.observe( editor.getBody(), {
					attributes: true,
					attributeFilter: ['class']
				} );
			}

			// Pass on body class name changes from the editor to the View iframes.
			editor.on( 'body-class-change', function() {
				var className = editor.getBody().className;

				editor.$( 'iframe[class="view-sandbox"]' ).each( function( i, iframe ) {
					// Make sure it is a local iframe.
					// jshint scripturl: true
					if ( ! iframe.src || iframe.src === 'javascript:""' ) {
						try {
							iframe.contentWindow.document.body.className = className;
						} catch( er ) {}
					}
				});
			} );
		});

		// Scan new content for matching view patterns and replace them with markers.
		editor.on( 'beforesetcontent', function( event ) {
			var node;

			if ( ! event.selection ) {
				.mce.views.unbind();
			}

			if ( ! event.content ) {
				return;
			}

			if ( ! event.load ) {
				node = editor.selection.getNode();

				if ( node && node !== editor.getBody() && /^\s*https?:\/\/\S+\s*$/i.test( event.content ) ) {
					// When a url is pasted or inserted, only try to embed it when it is in an empty paragraph.
					node = editor.dom.getParent( node, 'p' );

					if ( node && /^[\s\uFEFF\u00A0]*$/.test( editor.$( node ).text() || '' ) ) {
						// Make sure there are no empty inline elements in the <p>.
						node.innerHTML = '';
					} else {
						return;
					}
				}
			}

			event.content = .mce.views.setMarkers( event.content, editor );
		} );

		// Replace any new markers nodes with views.
		editor.on( 'setcontent', function() {
			.mce.views.render();
		} );

		// Empty view nodes for easier processing.
		editor.on( 'preprocess hide', function( event ) {
			editor.$( 'div[data-view-text], p[data-view-marker]', event.node ).each( function( i, node ) {
				node.innerHTML = '.';
			} );
		}, true );

		// Replace views with their text.
		editor.on( 'postprocess', function( event ) {
			event.content = resetViews( event.content );
		} );

		// Prevent adding of undo levels when replacing view markers
		// or when there are changes only in the (non-editable) previews.
		editor.on( 'beforeaddundo', function( event ) {
			var lastContent;
			var newContent = event.level.content || ( event.level.fragments && event.level.fragments.join( '' ) );

			if ( ! event.lastLevel ) {
				lastContent = editor.startContent;
			} else {
				lastContent = event.lastLevel.content || ( event.lastLevel.fragments && event.lastLevel.fragments.join( '' ) );
			}

			if (
				! newContent ||
				! lastContent ||
				newContent.indexOf( ' data-view-' ) === -1 ||
				lastContent.indexOf( ' data-view-' ) === -1
			) {
				return;
			}

			if ( resetViews( lastContent ) === resetViews( newContent ) ) {
				event.preventDefault();
			}
		} );

		// Make sure views are copied as their text.
		editor.on( 'drop objectselected', function( event ) {
			if ( isView( event.targetClone ) ) {
				event.targetClone = editor.getDoc().createTextNode(
					window.decodeURIComponent( editor.dom.getAttrib( event.targetClone, 'data-view-text' ) )
				);
			}
		} );

		// Clean up URLs for easier processing.
		editor.on( 'pastepreprocess', function( event ) {
			var content = event.content;

			if ( content ) {
				content = tinymce.trim( content.replace( /<[^>]+>/g, '' ) );

				if ( /^https?:\/\/\S+$/i.test( content ) ) {
					event.content = content;
				}
			}
		} );

		// Show the view type in the element path.
		editor.on( 'resolvename', function( event ) {
			if ( isView( event.target ) ) {
				event.name = editor.dom.getAttrib( event.target, 'data-view-type' ) || 'object';
			}
		} );

		// See `media` plugin.
		editor.on( 'click keyup', function() {
			var node = editor.selection.getNode();

			if ( isView( node ) ) {
				if ( editor.dom.getAttrib( node, 'data-mce-selected' ) ) {
					node.setAttribute( 'data-mce-selected', '2' );
				}
			}
		} );

		editor.addButton( '_view_edit', {
			tooltip: 'Edit|button', // '|button' is not displayed, only used for context.
			icon: 'dashicon dashicons-edit',
			onclick: function() {
				var node = editor.selection.getNode();

				if ( isView( node ) ) {
					.mce.views.edit( editor, node );
				}
			}
		} );

		editor.addButton( '_view_remove', {
			tooltip: 'Remove',
			icon: 'dashicon dashicons-no',
			onclick: function() {
				editor.fire( 'cut' );
			}
		} );

		editor.once( 'preinit', function() {
			var toolbar;

			if ( editor. && editor.._createToolbar ) {
				toolbar = editor.._createToolbar( [
					'_view_edit',
					'_view_remove'
				] );

				editor.on( 'toolbar', function( event ) {
					if ( ! event.collapsed && isView( event.element ) ) {
						event.toolbar = toolbar;
					}
				} );
			}
		} );

		editor. = editor. || {};
		editor..getView = noop;
		editor..setViewCursor = noop;

		return {
			getView: noop
		};
	} );
} )( window.tinymce );
