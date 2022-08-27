<?php
/**
 * Widget API: _Widget_Media_Gallery class
 *
 * @package 
 * @subpackage Widgets
 * @since 4.9.0
 */

/**
 * Core class that implements a gallery widget.
 *
 * @since 4.9.0
 *
 * @see _Widget_Media
 * @see _Widget
 */
class _Widget_Media_Gallery extends _Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct(
			'media_gallery',
			__( 'Gallery' ),
			array(
				'description' => __( 'Displays an image gallery.' ),
				'mime_type'   => 'image',
			)
		);

		$this->l10n = array_merge(
			$this->l10n,
			array(
				'no_media_selected' => __( 'No images selected' ),
				'add_media'         => _x( 'Add Images', 'label for button in the gallery widget; should not be longer than ~13 characters long' ),
				'replace_media'     => '',
				'edit_media'        => _x( 'Edit Gallery', 'label for button in the gallery widget; should not be longer than ~13 characters long' ),
			)
		);
	}

	/**
	 * Get schema for properties of a widget instance (item).
	 *
	 * @since 4.9.0
	 *
	 * @see _REST_Controller::get_item_schema()
	 * @see _REST_Controller::get_additional_fields()
	 * @link https://core.trac..org/ticket/35574
	 *
	 * @return array Schema for properties.
	 */
	public function get_instance_schema() {
		$schema = array(
			'title'          => array(
				'type'                  => 'string',
				'default'               => '',
				'sanitize_callback'     => 'sanitize_text_field',
				'description'           => __( 'Title for the widget' ),
				'should_preview_update' => false,
			),
			'ids'            => array(
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => '_parse_id_list',
			),
			'columns'        => array(
				'type'    => 'integer',
				'default' => 3,
				'minimum' => 1,
				'maximum' => 9,
			),
			'size'           => array(
				'type'    => 'string',
				'enum'    => array_merge( get_intermediate_image_sizes(), array( 'full', 'custom' ) ),
				'default' => 'thumbnail',
			),
			'link_type'      => array(
				'type'                  => 'string',
				'enum'                  => array( 'post', 'file', 'none' ),
				'default'               => 'post',
				'media_prop'            => 'link',
				'should_preview_update' => false,
			),
			'orderby_random' => array(
				'type'                  => 'boolean',
				'default'               => false,
				'media_prop'            => '_orderbyRandom',
				'should_preview_update' => false,
			),
		);

		/** This filter is documented in includes/widgets/class-widget-media.php */
		$schema = apply_filters( "widget_{$this->id_base}_instance_schema", $schema, $this );

		return $schema;
	}

	/**
	 * Render the media on the frontend.
	 *
	 * @since 4.9.0
	 *
	 * @param array $instance Widget instance props.
	 */
	public function render_media( $instance ) {
		$instance = array_merge( _list_pluck( $this->get_instance_schema(), 'default' ), $instance );

		$shortcode_atts = array_merge(
			$instance,
			array(
				'link' => $instance['link_type'],
			)
		);

		// @codeCoverageIgnoreStart
		if ( $instance['orderby_random'] ) {
			$shortcode_atts['orderby'] = 'rand';
		}

		// @codeCoverageIgnoreEnd
		echo gallery_shortcode( $shortcode_atts );
	}

	/**
	 * Loads the required media files for the media manager and scripts for media widgets.
	 *
	 * @since 4.9.0
	 */
	public function enqueue_admin_scripts() {
		parent::enqueue_admin_scripts();

		$handle = 'media-gallery-widget';
		_enqueue_script( $handle );

		$exported_schema = array();
		foreach ( $this->get_instance_schema() as $field => $field_schema ) {
			$exported_schema[ $field ] = _array_slice_assoc( $field_schema, array( 'type', 'default', 'enum', 'minimum', 'format', 'media_prop', 'should_preview_update', 'items' ) );
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
					_.extend( .mediaWidgets.controlConstructors[ %1$s ].prototype.l10n, %3$s );
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
	 * @since 4.9.0
	 */
	public function render_control_template_scripts() {
		parent::render_control_template_scripts();
		?>
		<script type="text/html" id="tmpl-media-widget-gallery-preview">
			<#
			var ids = _.filter( data.ids, function( id ) {
				return ( id in data.attachments );
			} );
			#>
			<# if ( ids.length ) { #>
				<ul class="gallery media-widget-gallery-preview" role="list">
					<# _.each( ids, function( id, index ) { #>
						<# var attachment = data.attachments[ id ]; #>
						<# if ( index < 6 ) { #>
							<li class="gallery-item">
								<div class="gallery-icon">
									<img alt="{{ attachment.alt }}"
										<# if ( index === 5 && data.ids.length > 6 ) { #> aria-hidden="true" <# } #>
										<# if ( attachment.sizes.thumbnail ) { #>
											src="{{ attachment.sizes.thumbnail.url }}" width="{{ attachment.sizes.thumbnail.width }}" height="{{ attachment.sizes.thumbnail.height }}"
										<# } else { #>
											src="{{ attachment.url }}"
										<# } #>
										<# if ( ! attachment.alt && attachment.filename ) { #>
											aria-label="
											<?php
											echo esc_attr(
												sprintf(
													/* translators: %s: The image file name. */
													__( 'The current image has no alternative text. The file name is: %s' ),
													'{{ attachment.filename }}'
												)
											);
											?>
											"
										<# } #>
									/>
									<# if ( index === 5 && data.ids.length > 6 ) { #>
									<div class="gallery-icon-placeholder">
										<p class="gallery-icon-placeholder-text" aria-label="
										<?php
											printf(
												/* translators: %s: The amount of additional, not visible images in the gallery widget preview. */
												__( 'Additional images added to this gallery: %s' ),
												'{{ data.ids.length - 5 }}'
											);
										?>
										">+{{ data.ids.length - 5 }}</p>
									</div>
									<# } #>
								</div>
							</li>
						<# } #>
					<# } ); #>
				</ul>
			<# } else { #>
				<div class="attachment-media-view">
					<button type="button" class="placeholder button-add-media"><?php echo esc_html( $this->l10n['add_media'] ); ?></button>
				</div>
			<# } #>
		</script>
		<?php
	}

	/**
	 * Whether the widget has content to show.
	 *
	 * @since 4.9.0
	 * @access protected
	 *
	 * @param array $instance Widget instance props.
	 * @return bool Whether widget has content.
	 */
	protected function has_content( $instance ) {
		if ( ! empty( $instance['ids'] ) ) {
			$attachments = _parse_id_list( $instance['ids'] );
			foreach ( $attachments as $attachment ) {
				if ( 'attachment' !== get_post_type( $attachment ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
}
