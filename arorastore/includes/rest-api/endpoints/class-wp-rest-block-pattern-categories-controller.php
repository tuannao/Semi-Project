<?php
/**
 * REST API: _REST_Block_Pattern_Catergories_Controller class
 *
 * @package    
 * @subpackage REST_API
 * @since      6.0.0
 */

/**
 * Core class used to access block pattern categories via the REST API.
 *
 * @since 6.0.0
 *
 * @see _REST_Controller
 */
class _REST_Block_Pattern_Categories_Controller extends _REST_Controller {

	/**
	 * Constructs the controller.
	 *
	 * @since 6.0.0
	 */
	public function __construct() {
		$this->namespace = '/v2';
		$this->rest_base = 'block-patterns/categories';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 6.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => _REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read block patterns.
	 *
	 * @since 6.0.0
	 *
	 * @param _REST_Request $request Full details about the request.
	 * @return true|_Error True if the request has read access, _Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
			if ( current_user_can( $post_type->cap->edit_posts ) ) {
				return true;
			}
		}

		return new _Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to view the registered block pattern categories.' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Retrieves all block pattern categories.
	 *
	 * @since 6.0.0
	 *
	 * @param _REST_Request $request Full details about the request.
	 * @return _Error|_REST_Response Response object on success, or _Error object on failure.
	 */
	public function get_items( $request ) {
		$response   = array();
		$categories = _Block_Pattern_Categories_Registry::get_instance()->get_all_registered();
		foreach ( $categories as $category ) {
			$prepared_category = $this->prepare_item_for_response( $category, $request );
			$response[]        = $this->prepare_response_for_collection( $prepared_category );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a raw block pattern category before it gets output in a REST API response.
	 *
	 * @since 6.0.0
	 *
	 * @param array           $item    Raw category as registered, before any changes.
	 * @param _REST_Request $request Request object.
	 * @return _REST_Response|_Error Response object on success, or _Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$keys   = array( 'name', 'label' );
		$data   = array();
		foreach ( $keys as $key ) {
			if ( rest_is_field_included( $key, $fields ) ) {
				$data[ $key ] = $item[ $key ];
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the block pattern category schema, conforming to JSON Schema.
	 *
	 * @since 6.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'block-pattern-category',
			'type'       => 'object',
			'properties' => array(
				'name'  => array(
					'description' => __( 'The category name.' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'label' => array(
					'description' => __( 'The category label, in human readable format.' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
