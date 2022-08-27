<?php
/**
 * Widget API: _Widget_Media_Audio class
 *
 * @package 
 * @subpackage Widgets
 * @since 4.8.0
 */

/**
 * Core class that implements an audio widget.
 *
 * @since 4.8.0
 *
 * @see _Widget_Media
 * @see _Widget
 */
class _Widget_Media_Audio extends _Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 */
	public function __construct() {
		parent::__construct(
			'media_audio',
			__( 'Audio' ),
			array(
				'description' => __( 'Displays an audio player.' ),
				'mime_type'   => 'audio',
			)
		);

		$this->l10n = array_merge(
			$this->l10n,
			array(
				'no_media_selected'          => __( 'No audio selected' ),
				'add_media'                  => _x( 'Add Audio', 'label for button in the audio widget' ),
				'replace_media'              => _x( 'Replace Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'edit_media'                 => _x( 'Edit Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'missing_attachment'         => sprintf(
					/* translators: %s: URL to media library. */
					__( 'That audio file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.' ),
					esc_url( admin_url( 'upload.php' ) )
				),
				/* translators: %d: Widget count. */
				'media_library_state_multi'  => _n_noop( 'Audio Widget (%d)', 'Audio Widget (%d)' ),
				'media_library_state_single' => __( 'Audio Widget' ),
				'unsupported_file_type'      => __( 'Looks like this is not the correct kind of file. Please link to an audio file instead.' ),
			)
		);
	}

	/**
	 * Get schema for properties of a widget instance (item).
	 *
	 * @since 4.8.0
	 *
	 * @see _REST_Controller::get_item_schema()
	 * @see _REST_Controller::get_additional_fields()
	 * @link https://core.trac..org/ticket/35574
	 *
	 * @return array Schema for properties.
	 */
	public function get_instance_schema() {
		$schema = array(
			'preload' => array(
				'type'        => 'string',
				'enum'        => array( 'none', 'auto', 'metadata' ),
				'default'     => 'none',
				'description' => __( 'Preload' ),
			),
			'loop'    => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Loop' ),
			),
		);

		foreach ( _get_audio_extensions() as $audio_extension ) {
			$schema[ $audio_extension ] = array(
				'type'        => 'string',
				'default'     => '',
				'format'      => 'uri',
				/* translators: %s: Audio extension. */
				'description' => sprintf( __( 'URL to the %s audio source file' ), $audio_extension ),
			);
		}

		return array_merge( $schema, parent::get_instance_schema() );
	}

	/**
	 * Render the media on the frontend.
	 *
	 * @since 4.8.0
	 *
	 * @param array $instance Widget instance props.
	 */
	public function render_media( $instance ) {
		$instance   = array_merge( _list_pluck( $this->get_instance_schema(), 'default' ), $instance );
		$attachment = null;

		if ( $this->is_attachment_with_mime_type( $instance['attachment_id'], $this->widget_options['mime_type'] ) ) {
			$attachment = get_post( $instance['attachment_id'] );
		}

		if ( $attachment ) {
			$src = _get_attachment_url( $attachment->ID );
		} else {
			$src = $instance['url'];
		}

		echo _audio_shortcode(
			array_merge(
				$instance,
				compact( 'src' )
			)
		);
	}

	/**
	 * Enqueue preview scripts.
	 *
	 * These scripts normally are enqueued just-in-time when an audio shortcode is used.
	 * In the customizer, however, widgets can be dynamically added and rendered via
	 * selective refresh, and so it is important to unconditionally enqueue them in
	 * case a widget does get added.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_preview_scripts() {
		/** This filter is documented in includes/media.php */
		if ( 'mediaelement' === apply_filters( '_audio_shortcode_library', 'mediaelement' ) ) {
			_enqueue_style( 'mediaelement' );
			_enqueue_script( 'mediaelement' );
		}
	}

	/**
	 * Loads the required media files for the media manager and scripts for media widgets.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_admin_scripts() {
		parent::enqueue_admin_scripts();

		_enqueue_style( 'mediaelement' );
		_enqueue_script( 'mediaelement' );

		$handle = 'media-audio-widget';
		_enqueue_script( $handle );

		$exported_schema = array();
		foreach ( $this->get_instance_schema() as $field => $field_schema ) {
			$exported_schema[ $field ] = _array_slice_assoc( $field_schema, array( 'type', 'default', 'enum', 'minimum', 'format', 'media_prop', 'should_preview_update' ) );
		}
		_add_inline_script(
			$handle,
			sprintf(
				'.mediaWidgets.modelConstructors[ %s ].prototype.schema = %s;',
				_json_encode( $this->id_base ),
				_json_encode( $exported_schema )
			)
		);

		_add_inline_script(
			$handle,
			sprintf(
				'
					.mediaWidgets.controlConstructors[ %1$s ].prototype.mime_type = %2$s;
					.mediaWidgets.controlConstructors[ %1$s ].prototype.l10n = _.extend( {}, .mediaWidgets.controlConstructors[ %1$s ].prototype.l10n, %3$s );
				',
				_json_encode( $this->id_base ),
				_json_encode( $this->widget_options['mime_type'] ),
				_json_encode( $this->l10n )
			)
		);
	}

	/**
	 * Render form template scripts.
	 *
	 * @since 4.8.0
	 */
	public function render_control_template_scripts() {
		parent::render_control_template_scripts()
		?>
		<script type="text/html" id="tmpl-media-widget-audio-preview">
			<# if ( data.error && 'missing_attachment' === data.error ) { #>
				<div class="notice notice-error notice-alt notice-missing-attachment">
					<p><?php echo $this->l10n['missing_attachment']; ?></p>
				</div>
			<# } else if ( data.error ) { #>
				<div class="notice notice-error notice-alt">
					<p><?php _e( 'Unable to preview media due to an unknown error.' ); ?></p>
				</div>
			<# } else if ( data.model && data.model.src ) { #>
				<?php _underscore_audio_template(); ?>
			<# } #>
		</script>
		<?php
	}
}
