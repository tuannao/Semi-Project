/**
 * @output includes/js/lists.js
 */

/* global ajaxurl, Ajax */

/**
 * @param {jQuery} $ jQuery object.
 */
( function( $ ) {
var functions = {
	add:     'ajaxAdd',
	del:     'ajaxDel',
	dim:     'ajaxDim',
	process: 'process',
	recolor: 'recolor'
}, List;

/**
 * @namespace
 */
List = {

	/**
	 * @member {object}
	 */
	settings: {

		/**
		 * URL for Ajax requests.
		 *
		 * @member {string}
		 */
		url: ajaxurl,

		/**
		 * The HTTP method to use for Ajax requests.
		 *
		 * @member {string}
		 */
		type: 'POST',

		/**
		 * ID of the element the parsed Ajax response will be stored in.
		 *
		 * @member {string}
		 */
		response: 'ajax-response',

		/**
		 * The type of list.
		 *
		 * @member {string}
		 */
		what: '',

		/**
		 * CSS class name for alternate styling.
		 *
		 * @member {string}
		 */
		alt: 'alternate',

		/**
		 * Offset to start alternate styling from.
		 *
		 * @member {number}
		 */
		altOffset: 0,

		/**
		 * Color used in animation when adding an element.
		 *
		 * Can be 'none' to disable the animation.
		 *
		 * @member {string}
		 */
		addColor: '#ffff33',

		/**
		 * Color used in animation when deleting an element.
		 *
		 * Can be 'none' to disable the animation.
		 *
		 * @member {string}
		 */
		delColor: '#faafaa',

		/**
		 * Color used in dim add animation.
		 *
		 * Can be 'none' to disable the animation.
		 *
		 * @member {string}
		 */
		dimAddColor: '#ffff33',

		/**
		 * Color used in dim delete animation.
		 *
		 * Can be 'none' to disable the animation.
		 *
		 * @member {string}
		 */
		dimDelColor: '#ff3333',

		/**
		 * Callback that's run before a request is made.
		 *
		 * @callback List~confirm
		 * @param {object}      this
		 * @param {HTMLElement} list            The list DOM element.
		 * @param {object}      settings        Settings for the current list.
		 * @param {string}      action          The type of action to perform: 'add', 'delete', or 'dim'.
		 * @param {string}      backgroundColor Background color of the list's DOM element.
		 * @return {boolean} Whether to proceed with the action or not.
		 */
		confirm: null,

		/**
		 * Callback that's run before an item gets added to the list.
		 *
		 * Allows to cancel the request.
		 *
		 * @callback List~addBefore
		 * @param {object} settings Settings for the Ajax request.
		 * @return {object|boolean} Settings for the Ajax request or false to abort.
		 */
		addBefore: null,

		/**
		 * Callback that's run after an item got added to the list.
		 *
		 * @callback List~addAfter
		 * @param {XML}    returnedResponse Raw response returned from the server.
		 * @param {object} settings         Settings for the Ajax request.
		 * @param {jqXHR}  settings.xml     jQuery XMLHttpRequest object.
		 * @param {string} settings.status  Status of the request: 'success', 'notmodified', 'nocontent', 'error',
		 *                                  'timeout', 'abort', or 'parsererror'.
		 * @param {object} settings.parsed  Parsed response object.
		 */
		addAfter: null,

		/**
		 * Callback that's run before an item gets deleted from the list.
		 *
		 * Allows to cancel the request.
		 *
		 * @callback List~delBefore
		 * @param {object}      settings Settings for the Ajax request.
		 * @param {HTMLElement} list     The list DOM element.
		 * @return {object|boolean} Settings for the Ajax request or false to abort.
		 */
		delBefore: null,

		/**
		 * Callback that's run after an item got deleted from the list.
		 *
		 * @callback List~delAfter
		 * @param {XML}    returnedResponse Raw response returned from the server.
		 * @param {object} settings         Settings for the Ajax request.
		 * @param {jqXHR}  settings.xml     jQuery XMLHttpRequest object.
		 * @param {string} settings.status  Status of the request: 'success', 'notmodified', 'nocontent', 'error',
		 *                                  'timeout', 'abort', or 'parsererror'.
		 * @param {object} settings.parsed  Parsed response object.
		 */
		delAfter: null,

		/**
		 * Callback that's run before an item gets dim'd.
		 *
		 * Allows to cancel the request.
		 *
		 * @callback List~dimBefore
		 * @param {object} settings Settings for the Ajax request.
		 * @return {object|boolean} Settings for the Ajax request or false to abort.
		 */
		dimBefore: null,

		/**
		 * Callback that's run after an item got dim'd.
		 *
		 * @callback List~dimAfter
		 * @param {XML}    returnedResponse Raw response returned from the server.
		 * @param {object} settings         Settings for the Ajax request.
		 * @param {jqXHR}  settings.xml     jQuery XMLHttpRequest object.
		 * @param {string} settings.status  Status of the request: 'success', 'notmodified', 'nocontent', 'error',
		 *                                  'timeout', 'abort', or 'parsererror'.
		 * @param {object} settings.parsed  Parsed response object.
		 */
		dimAfter: null
	},

	/**
	 * Finds a nonce.
	 *
	 * 1. Nonce in settings.
	 * 2. `_ajax_nonce` value in element's href attribute.
	 * 3. `_ajax_nonce` input field that is a descendant of element.
	 * 4. `_nonce` value in element's href attribute.
	 * 5. `_nonce` input field that is a descendant of element.
	 * 6. 0 if none can be found.
	 *
	 * @param {jQuery} element  Element that triggered the request.
	 * @param {Object} settings Settings for the Ajax request.
	 * @return {string|number} Nonce
	 */
	nonce: function( element, settings ) {
		var url      = Ajax.unserialize( element.attr( 'href' ) ),
			$element = $( '#' + settings.element );

		return settings.nonce || url._ajax_nonce || $element.find( 'input[name="_ajax_nonce"]' ).val() || url._nonce || $element.find( 'input[name="_nonce"]' ).val() || 0;
	},

	/**
	 * Extract list item data from a DOM element.
	 *
	 * Example 1: data-lists="delete:the-comment-list:comment-{comment_ID}:66cc66:unspam=1"
	 * Example 2: data-lists="dim:the-comment-list:comment-{comment_ID}:unapproved:e7e7d3:e7e7d3:new=approved"
	 *
	 * Returns an unassociative array with the following data:
	 * data[0] - Data identifier: 'list', 'add', 'delete', or 'dim'.
	 * data[1] - ID of the corresponding list. If data[0] is 'list', the type of list ('comment', 'category', etc).
	 * data[2] - ID of the parent element of all inputs necessary for the request.
	 * data[3] - Hex color to be used in this request. If data[0] is 'dim', dim class.
	 * data[4] - Additional arguments in query syntax that are added to the request. Example: 'post_id=1234'.
	 *           If data[0] is 'dim', dim add color.
	 * data[5] - Only available if data[0] is 'dim', dim delete color.
	 * data[6] - Only available if data[0] is 'dim', additional arguments in query syntax that are added to the request.
	 *
	 * Result for Example 1:
	 * data[0] - delete
	 * data[1] - the-comment-list
	 * data[2] - comment-{comment_ID}
	 * data[3] - 66cc66
	 * data[4] - unspam=1
	 *
	 * @param {HTMLElement} element The DOM element.
	 * @param {string}      type    The type of data to look for: 'list', 'add', 'delete', or 'dim'.
	 * @return {Array} Extracted list item data.
	 */
	parseData: function( element, type ) {
		var data = [], ListsData;

		try {
			ListsData = $( element ).data( 'lists' ) || '';
			ListsData = ListsData.match( new RegExp( type + ':[\\S]+' ) );

			if ( ListsData ) {
				data = ListsData[0].split( ':' );
			}
		} catch ( error ) {}

		return data;
	},

	/**
	 * Calls a confirm callback to verify the action that is about to be performed.
	 *
	 * @param {HTMLElement} list     The DOM element.
	 * @param {Object}      settings Settings for this list.
	 * @param {string}      action   The type of action to perform: 'add', 'delete', or 'dim'.
	 * @return {Object|boolean} Settings if confirmed, false if not.
	 */
	pre: function( list, settings, action ) {
		var $element, backgroundColor, confirmed;

		settings = $.extend( {}, this.List.settings, {
			element: null,
			nonce:   0,
			target:  list.get( 0 )
		}, settings || {} );

		if ( typeof settings.confirm === 'function' ) {
			$element = $( '#' + settings.element );

			if ( 'add' !== action ) {
				backgroundColor = $element.css( 'backgroundColor' );
				$element.css( 'backgroundColor', '#ff9966' );
			}

			confirmed = settings.confirm.call( this, list, settings, action, backgroundColor );

			if ( 'add' !== action ) {
				$element.css( 'backgroundColor', backgroundColor );
			}

			if ( ! confirmed ) {
				return false;
			}
		}

		return settings;
	},

	/**
	 * Adds an item to the list via Ajax.
	 *
	 * @param {HTMLElement} element  The DOM element.
	 * @param {Object}      settings Settings for this list.
	 * @return {boolean} Whether the item was added.
	 */
	ajaxAdd: function( element, settings ) {
		var list     = this,
			$element = $( element ),
			data     = List.parseData( $element, 'add' ),
			formValues, formData, parsedResponse, returnedResponse;

		settings = settings || {};
		settings = List.pre.call( list, $element, settings, 'add' );

		settings.element  = data[2] || $element.prop( 'id' ) || settings.element || null;
		settings.addColor = data[3] ? '#' + data[3] : settings.addColor;

		if ( ! settings ) {
			return false;
		}

		if ( ! $element.is( '[id="' + settings.element + '-submit"]' ) ) {
			return ! List.add.call( list, $element, settings );
		}

		if ( ! settings.element ) {
			return true;
		}

		settings.action = 'add-' + settings.what;
		settings.nonce  = List.nonce( $element, settings );

		if ( ! Ajax.validateForm( '#' + settings.element ) ) {
			return false;
		}

		settings.data = $.param( $.extend( {
			_ajax_nonce: settings.nonce,
			action:      settings.action
		}, Ajax.unserialize( data[4] || '' ) ) );

		formValues = $( '#' + settings.element + ' :input' ).not( '[name="_ajax_nonce"], [name="_nonce"], [name="action"]' );
		formData   = typeof formValues.fieldSerialize === 'function' ? formValues.fieldSerialize() : formValues.serialize();

		if ( formData ) {
			settings.data += '&' + formData;
		}

		if ( typeof settings.addBefore === 'function' ) {
			settings = settings.addBefore( settings );

			if ( ! settings ) {
				return true;
			}
		}

		if ( ! settings.data.match( /_ajax_nonce=[a-f0-9]+/ ) ) {
			return true;
		}

		settings.success = function( response ) {
			parsedResponse   = Ajax.parseAjaxResponse( response, settings.response, settings.element );
			returnedResponse = response;

			if ( ! parsedResponse || parsedResponse.errors ) {
				return false;
			}

			if ( true === parsedResponse ) {
				return true;
			}

			$.each( parsedResponse.responses, function() {
				List.add.call( list, this.data, $.extend( {}, settings, { // this.firstChild.nodevalue
					position: this.position || 0,
					id:       this.id || 0,
					oldId:    this.oldId || null
				} ) );
			} );

			list.List.recolor();
			$( list ).trigger( 'ListAddEnd', [ settings, list.List ] );
			List.clear.call( list, '#' + settings.element );
		};

		settings.complete = function( jqXHR, status ) {
			if ( typeof settings.addAfter === 'function' ) {
				settings.addAfter( returnedResponse, $.extend( {
					xml:    jqXHR,
					status: status,
					parsed: parsedResponse
				}, settings ) );
			}
		};

		$.ajax( settings );

		return false;
	},

	/**
	 * Delete an item in the list via Ajax.
	 *
	 * @param {HTMLElement} element  A DOM element containing item data.
	 * @param {Object}      settings Settings for this list.
	 * @return {boolean} Whether the item was deleted.
	 */
	ajaxDel: function( element, settings ) {
		var list     = this,
			$element = $( element ),
			data     = List.parseData( $element, 'delete' ),
			$eventTarget, parsedResponse, returnedResponse;

		settings = settings || {};
		settings = List.pre.call( list, $element, settings, 'delete' );

		settings.element  = data[2] || settings.element || null;
		settings.delColor = data[3] ? '#' + data[3] : settings.delColor;

		if ( ! settings || ! settings.element ) {
			return false;
		}

		settings.action = 'delete-' + settings.what;
		settings.nonce  = List.nonce( $element, settings );

		settings.data = $.extend( {
			_ajax_nonce: settings.nonce,
			action:      settings.action,
			id:          settings.element.split( '-' ).pop()
		}, Ajax.unserialize( data[4] || '' ) );

		if ( typeof settings.delBefore === 'function' ) {
			settings = settings.delBefore( settings, list );

			if ( ! settings ) {
				return true;
			}
		}

		if ( ! settings.data._ajax_nonce ) {
			return true;
		}

		$eventTarget = $( '#' + settings.element );

		if ( 'none' !== settings.delColor ) {
			$eventTarget.css( 'backgroundColor', settings.delColor ).fadeOut( 350, function() {
				list.List.recolor();
				$( list ).trigger( 'ListDelEnd', [ settings, list.List ] );
			} );
		} else {
			list.List.recolor();
			$( list ).trigger( 'ListDelEnd', [ settings, list.List ] );
		}

		settings.success = function( response ) {
			parsedResponse   = Ajax.parseAjaxResponse( response, settings.response, settings.element );
			returnedResponse = response;

			if ( ! parsedResponse || parsedResponse.errors ) {
				$eventTarget.stop().stop().css( 'backgroundColor', '#faa' ).show().queue( function() {
					list.List.recolor();
					$( this ).dequeue();
				} );

				return false;
			}
		};

		settings.complete = function( jqXHR, status ) {
			if ( typeof settings.delAfter === 'function' ) {
				$eventTarget.queue( function() {
					settings.delAfter( returnedResponse, $.extend( {
						xml:    jqXHR,
						status: status,
						parsed: parsedResponse
					}, settings ) );
				} ).dequeue();
			}
		};

		$.ajax( settings );

		return false;
	},

	/**
	 * Dim an item in the list via Ajax.
	 *
	 * @param {HTMLElement} element  A DOM element containing item data.
	 * @param {Object}      settings Settings for this list.
	 * @return {boolean} Whether the item was dim'ed.
	 */
	ajaxDim: function( element, settings ) {
		var list     = this,
			$element = $( element ),
			data     = List.parseData( $element, 'dim' ),
			$eventTarget, isClass, color, dimColor, parsedResponse, returnedResponse;

		// Prevent hidden links from being clicked by hotkeys.
		if ( 'none' === $element.parent().css( 'display' ) ) {
			return false;
		}

		settings = settings || {};
		settings = List.pre.call( list, $element, settings, 'dim' );

		settings.element     = data[2] || settings.element || null;
		settings.dimClass    = data[3] || settings.dimClass || null;
		settings.dimAddColor = data[4] ? '#' + data[4] : settings.dimAddColor;
		settings.dimDelColor = data[5] ? '#' + data[5] : settings.dimDelColor;

		if ( ! settings || ! settings.element || ! settings.dimClass ) {
			return true;
		}

		settings.action = 'dim-' + settings.what;
		settings.nonce  = List.nonce( $element, settings );

		settings.data = $.extend( {
			_ajax_nonce: settings.nonce,
			action:      settings.action,
			id:          settings.element.split( '-' ).pop(),
			dimClass:    settings.dimClass
		}, Ajax.unserialize( data[6] || '' ) );

		if ( typeof settings.dimBefore === 'function' ) {
			settings = settings.dimBefore( settings );

			if ( ! settings ) {
				return true;
			}
		}

		$eventTarget = $( '#' + settings.element );
		isClass      = $eventTarget.toggleClass( settings.dimClass ).is( '.' + settings.dimClass );
		color        = List.getColor( $eventTarget );
		dimColor     = isClass ? settings.dimAddColor : settings.dimDelColor;
		$eventTarget.toggleClass( settings.dimClass );

		if ( 'none' !== dimColor ) {
			$eventTarget
				.animate( { backgroundColor: dimColor }, 'fast' )
				.queue( function() {
					$eventTarget.toggleClass( settings.dimClass );
					$( this ).dequeue();
				} )
				.animate( { backgroundColor: color }, {
					complete: function() {
						$( this ).css( 'backgroundColor', '' );
						$( list ).trigger( 'ListDimEnd', [ settings, list.List ] );
					}
				} );
		} else {
			$( list ).trigger( 'ListDimEnd', [ settings, list.List ] );
		}

		if ( ! settings.data._ajax_nonce ) {
			return true;
		}

		settings.success = function( response ) {
			parsedResponse   = Ajax.parseAjaxResponse( response, settings.response, settings.element );
			returnedResponse = response;

			if ( true === parsedResponse ) {
				return true;
			}

			if ( ! parsedResponse || parsedResponse.errors ) {
				$eventTarget.stop().stop().css( 'backgroundColor', '#ff3333' )[isClass ? 'removeClass' : 'addClass']( settings.dimClass ).show().queue( function() {
					list.List.recolor();
					$( this ).dequeue();
				} );

				return false;
			}

			/** @property {string} comment_link Link of the comment to be dimmed. */
			if ( 'undefined' !== typeof parsedResponse.responses[0].supplemental.comment_link ) {
				var $submittedOn = $element.find( '.submitted-on' ),
					$commentLink = $submittedOn.find( 'a' );

				// Comment is approved; link the date field.
				if ( '' !== parsedResponse.responses[0].supplemental.comment_link ) {
					$submittedOn.html( $('<a></a>').text( $submittedOn.text() ).prop( 'href', parsedResponse.responses[0].supplemental.comment_link ) );

				// Comment is not approved; unlink the date field.
				} else if ( $commentLink.length ) {
					$submittedOn.text( $commentLink.text() );
				}
			}
		};

		settings.complete = function( jqXHR, status ) {
			if ( typeof settings.dimAfter === 'function' ) {
				$eventTarget.queue( function() {
					settings.dimAfter( returnedResponse, $.extend( {
						xml:    jqXHR,
						status: status,
						parsed: parsedResponse
					}, settings ) );
				} ).dequeue();
			}
		};

		$.ajax( settings );

		return false;
	},

	/**
	 * Returns the background color of the passed element.
	 *
	 * @param {jQuery|string} element Element to check.
	 * @return {string} Background color value in HEX. Default: '#ffffff'.
	 */
	getColor: function( element ) {
		return $( element ).css( 'backgroundColor' ) || '#ffffff';
	},

	/**
	 * Adds something.
	 *
	 * @param {HTMLElement} element  A DOM element containing item data.
	 * @param {Object}      settings Settings for this list.
	 * @return {boolean} Whether the item was added.
	 */
	add: function( element, settings ) {
		var $list    = $( this ),
			$element = $( element ),
			old      = false,
			position, reference;

		if ( 'string' === typeof settings ) {
			settings = { what: settings };
		}

		settings = $.extend( { position: 0, id: 0, oldId: null }, this.List.settings, settings );

		if ( ! $element.length || ! settings.what ) {
			return false;
		}

		if ( settings.oldId ) {
			old = $( '#' + settings.what + '-' + settings.oldId );
		}

		if ( settings.id && ( settings.id !== settings.oldId || ! old || ! old.length ) ) {
			$( '#' + settings.what + '-' + settings.id ).remove();
		}

		if ( old && old.length ) {
			old.before( $element );
			old.remove();

		} else if ( isNaN( settings.position ) ) {
			position = 'after';

			if ( '-' === settings.position.substr( 0, 1 ) ) {
				settings.position = settings.position.substr( 1 );
				position = 'before';
			}

			reference = $list.find( '#' + settings.position );

			if ( 1 === reference.length ) {
				reference[position]( $element );
			} else {
				$list.append( $element );
			}

		} else if ( 'comment' !== settings.what || 0 === $( '#' + settings.element ).length ) {
			if ( settings.position < 0 ) {
				$list.prepend( $element );
			} else {
				$list.append( $element );
			}
		}

		if ( settings.alt ) {
			$element.toggleClass( settings.alt, ( $list.children( ':visible' ).index( $element[0] ) + settings.altOffset ) % 2 );
		}

		if ( 'none' !== settings.addColor ) {
			$element.css( 'backgroundColor', settings.addColor ).animate( { backgroundColor: List.getColor( $element ) }, {
				complete: function() {
					$( this ).css( 'backgroundColor', '' );
				}
			} );
		}

		// Add event handlers.
		$list.each( function( index, list ) {
			list.List.process( $element );
		} );

		return $element;
	},

	/**
	 * Clears all input fields within the element passed.
	 *
	 * @param {string} elementId ID of the element to check, including leading #.
	 */
	clear: function( elementId ) {
		var list     = this,
			$element = $( elementId ),
			type, tagName;

		// Bail if we're within the list.
		if ( list.List && $element.parents( '#' + list.id ).length ) {
			return;
		}

		// Check each input field.
		$element.find( ':input' ).each( function( index, input ) {

			// Bail if the form was marked to not to be cleared.
			if ( $( input ).parents( '.form-no-clear' ).length ) {
				return;
			}

			type    = input.type.toLowerCase();
			tagName = input.tagName.toLowerCase();

			if ( 'text' === type || 'password' === type || 'textarea' === tagName ) {
				input.value = '';

			} else if ( 'checkbox' === type || 'radio' === type ) {
				input.checked = false;

			} else if ( 'select' === tagName ) {
				input.selectedIndex = null;
			}
		} );
	},

	/**
	 * Registers event handlers to add, delete, and dim items.
	 *
	 * @param {string} elementId
	 */
	process: function( elementId ) {
		var list     = this,
			$element = $( elementId || document );

		$element.on( 'submit', 'form[data-lists^="add:' + list.id + ':"]', function() {
			return list.List.add( this );
		} );

		$element.on( 'click', 'a[data-lists^="add:' + list.id + ':"], input[data-lists^="add:' + list.id + ':"]', function() {
			return list.List.add( this );
		} );

		$element.on( 'click', '[data-lists^="delete:' + list.id + ':"]', function() {
			return list.List.del( this );
		} );

		$element.on( 'click', '[data-lists^="dim:' + list.id + ':"]', function() {
			return list.List.dim( this );
		} );
	},

	/**
	 * Updates list item background colors.
	 */
	recolor: function() {
		var list    = this,
			evenOdd = [':even', ':odd'],
			items;

		// Bail if there is no alternate class name specified.
		if ( ! list.List.settings.alt ) {
			return;
		}

		items = $( '.list-item:visible', list );

		if ( ! items.length ) {
			items = $( list ).children( ':visible' );
		}

		if ( list.List.settings.altOffset % 2 ) {
			evenOdd.reverse();
		}

		items.filter( evenOdd[0] ).addClass( list.List.settings.alt ).end();
		items.filter( evenOdd[1] ).removeClass( list.List.settings.alt );
	},

	/**
	 * Sets up `process()` and `recolor()` functions.
	 */
	init: function() {
		var $list = this;

		$list.List.process = function( element ) {
			$list.each( function() {
				this.List.process( element );
			} );
		};

		$list.List.recolor = function() {
			$list.each( function() {
				this.List.recolor();
			} );
		};
	}
};

/**
 * Initializes List object.
 *
 * @param {Object}           settings
 * @param {string}           settings.url         URL for ajax calls. Default: ajaxurl.
 * @param {string}           settings.type        The HTTP method to use for Ajax requests. Default: 'POST'.
 * @param {string}           settings.response    ID of the element the parsed ajax response will be stored in.
 *                                                Default: 'ajax-response'.
 *
 * @param {string}           settings.what        Default: ''.
 * @param {string}           settings.alt         CSS class name for alternate styling. Default: 'alternate'.
 * @param {number}           settings.altOffset   Offset to start alternate styling from. Default: 0.
 * @param {string}           settings.addColor    Hex code or 'none' to disable animation. Default: '#ffff33'.
 * @param {string}           settings.delColor    Hex code or 'none' to disable animation. Default: '#faafaa'.
 * @param {string}           settings.dimAddColor Hex code or 'none' to disable animation. Default: '#ffff33'.
 * @param {string}           settings.dimDelColor Hex code or 'none' to disable animation. Default: '#ff3333'.
 *
 * @param {List~confirm}   settings.confirm     Callback that's run before a request is made. Default: null.
 * @param {List~addBefore} settings.addBefore   Callback that's run before an item gets added to the list.
 *                                                Default: null.
 * @param {List~addAfter}  settings.addAfter    Callback that's run after an item got added to the list.
 *                                                Default: null.
 * @param {List~delBefore} settings.delBefore   Callback that's run before an item gets deleted from the list.
 *                                                Default: null.
 * @param {List~delAfter}  settings.delAfter    Callback that's run after an item got deleted from the list.
 *                                                Default: null.
 * @param {List~dimBefore} settings.dimBefore   Callback that's run before an item gets dim'd. Default: null.
 * @param {List~dimAfter}  settings.dimAfter    Callback that's run after an item got dim'd. Default: null.
 * @return {$.fn} List API function.
 */
$.fn.List = function( settings ) {
	this.each( function( index, list ) {
		list.List = {
			settings: $.extend( {}, List.settings, { what: List.parseData( list, 'list' )[1] || '' }, settings )
		};

		$.each( functions, function( func, callback ) {
			list.List[func] = function( element, setting ) {
				return List[callback].call( list, element, setting );
			};
		} );
	} );

	List.init.call( this );
	this.List.process();

	return this;
};
} ) ( jQuery );
