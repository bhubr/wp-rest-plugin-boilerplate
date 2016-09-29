<?php
namespace bhubr;
if ( ! class_exists( '\WP_REST_Controller' ) ) {
  require_once dirname( __FILE__ ) . '/vendor/class-wp-rest-controller.php';
}

class REST_Controller extends \WP_REST_Controller {
  protected $bases = [];

  /**
   * Set base routes for each types
   */
  // public function set_bases($bases) {
  //   $this->bases = $bases;
  // }

  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {
    $version = '1';
    $namespace = 'bhubr/v' . $version;
    foreach(Base_Model::get_rest_bases() as $base) {
      register_rest_route( $namespace, '/' . $base, array(
        array(
          'methods'         => \WP_REST_Server::READABLE,
          'callback'        => array( $this, 'get_items' ),
          'permission_callback' => array( $this, 'get_items_permissions_check' ),
          'args'            => array(

          ),
        ),
        array(
          'methods'         => \WP_REST_Server::CREATABLE,
          'callback'        => array( $this, 'create_item' ),
          'permission_callback' => array( $this, 'create_item_permissions_check' ),
          'args'            => $this->get_endpoint_args_for_item_schema( true ),
        ),
      ) );
      register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
        array(
          'methods'         => \WP_REST_Server::READABLE,
          'callback'        => array( $this, 'get_item' ),
          'permission_callback' => array( $this, 'get_item_permissions_check' ),
          'args'            => array(
            'context'          => array(
              'default'      => 'view',
            ),
          ),
        ),
        array(
          'methods'         => \WP_REST_Server::EDITABLE,
          'callback'        => array( $this, 'update_item' ),
          'permission_callback' => array( $this, 'update_item_permissions_check' ),
          'args'            => $this->get_endpoint_args_for_item_schema( false ),
        ),
        array(
          'methods'  => \WP_REST_Server::DELETABLE,
          'callback' => array( $this, 'delete_item' ),
          'permission_callback' => array( $this, 'delete_item_permissions_check' ),
          'args'     => array(
            'force'    => array(
              'default'      => false,
            ),
          ),
        ),
      ) );
      register_rest_route( $namespace, '/' . $base . '/schema', array(
        'methods'         => \WP_REST_Server::READABLE,
        'callback'        => array( $this, 'get_public_item_schema' ),
      ) );
    }
  }

  /**
   * Get a collection of items
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|\WP_REST_Response
   */
  public function get_items( $request ) {
    $route_bits = explode('/', $request->get_route());
    $type_lc = \Inflect::singularize(array_pop($route_bits));
    $rest_class = Base_Model::get_rest_route_class($type_lc);
    $items = $rest_class::_read_all($type_lc);
    $data = array();
    foreach( $items as $item ) {
      // $itemdata = $this->prepare_item_for_response( $item, $request );
      $data[] = $this->prepare_response_for_collection( $item );
    }

    return new \WP_REST_Response( $data, 200 );
  }

  /**
   * Get one item from the collection
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|\WP_REST_Response
   */
  public function get_item( $request ) {
    $route_bits = explode('/', $request->get_route());
    $id = (int)array_pop($route_bits); // get id
    $type_lc = \Inflect::singularize(array_pop($route_bits));
    $rest_class = Base_Model::get_rest_route_class($type_lc);
    $post = $rest_class::_read($type_lc, $id);
    if ( is_array( $post ) ) {
      return new \WP_REST_Response( $post, 200 );
    }

    //return a response or error based on some conditional
    if ( 1 == 1 ) {
      return new \WP_REST_Response( $data, 200 );
    }else{
      return new \WP_Error( 'code', __( 'message', 'text-domain' ) );
    }
  }

  /**
   * Create one item from the collection
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|\WP_REST_Request
   */
  public function create_item( $request ) {
    // var_dump($request->get_url_params());
    $route_bits = explode('/', $request->get_route());
    $type_lc = \Inflect::singularize(array_pop($route_bits));
    $attributes = $request->get_json_params();
    $rest_class = Base_Model::get_rest_route_class($type_lc);
    $data = $rest_class::_create($type_lc, $attributes);
    if ( is_array( $data ) ) {
      return new \WP_REST_Response( $data, 200 );
    }

    return new \WP_Error( 'cant-create', __( 'message', 'text-domain'), array( 'status' => 500 ) );
  }


  /**
   * Update one item from the collection
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|\WP_REST_Request
   */
  public function update_item( $request ) {
    $route_bits = explode('/', $request->get_route());
    $id = (int)array_pop($route_bits); // get id
    $type_lc = \Inflect::singularize(array_pop($route_bits));
    $attributes = $request->get_json_params();
    $rest_class = Base_Model::get_rest_route_class($type_lc);
    $post = $rest_class::_update($type_lc, $id, $attributes);

    if ( is_array( $post ) ) {
      return new \WP_REST_Response( $post, 200 );
    }

    return new \WP_Error( 'cant-update', __( 'message', 'text-domain'), array( 'status' => 500 ) );

  }

  /**
   * Delete one item from the collection
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|\WP_REST_Request
   */
  public function delete_item( $request ) {
    $item = $this->prepare_item_for_database( $request );

    $route_bits = explode('/', $request->get_route());
    $id = (int)array_pop($route_bits); // get id
    $type_lc = \Inflect::singularize(array_pop($route_bits));
    $rest_class = Base_Model::get_rest_route_class($type_lc);
    $deleted_post = $rest_class::_delete($type_lc, $id);
    if ( is_array( $deleted_post ) ) {
      return new \WP_REST_Response( ['success' => true, 'deleted' => $deleted_post], 200 );
    }
    // if ( function_exists( 'slug_some_function_to_delete_item')  ) {
    //   $deleted = slug_some_function_to_delete_item( $item );
    //   if (  $deleted  ) {
    //     return new \WP_REST_Response( true, 200 );
    //   }
    // }

    return new \WP_Error( 'cant-delete', __( 'message', 'text-domain'), array( 'status' => 500 ) );
  }

  /**
   * Check if a given request has access to get items
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|bool
   */
  public function get_items_permissions_check( $request ) {
    return true;
    // return current_user_can( 'manage_options' );
  }

  /**
   * Check if a given request has access to get a specific item
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|bool
   */
  public function get_item_permissions_check( $request ) {
    return $this->get_items_permissions_check( $request );
  }

  /**
   * Check if a given request has access to create items
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|bool
   */
  public function create_item_permissions_check( $request ) {
    return true;
    // return current_user_can( 'manage_options' );
  }

  /**
   * Check if a given request has access to update a specific item
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|bool
   */
  public function update_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }

  /**
   * Check if a given request has access to delete a specific item
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_Error|bool
   */
  public function delete_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }

  /**
   * Prepare the item for create or update operation
   *
   * @param \WP_REST_Request $request Request object
   * @return \WP_Error|object $prepared_item
   */
  protected function prepare_item_for_database( $request ) {
    return array();
  }

  /**
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param \WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response( $item, $request ) {
    return array();
  }

  /**
   * Get the query params for collections
   *
   * @return array
   */
  public function get_collection_params() {
    return array(
      'page'                   => array(
        'description'        => 'Current page of the collection.',
        'type'               => 'integer',
        'default'            => 1,
        'sanitize_callback'  => 'absint',
      ),
      'per_page'               => array(
        'description'        => 'Maximum number of items to be returned in result set.',
        'type'               => 'integer',
        'default'            => 10,
        'sanitize_callback'  => 'absint',
      ),
      'search'                 => array(
        'description'        => 'Limit results to those matching a string.',
        'type'               => 'string',
        'sanitize_callback'  => 'sanitize_text_field',
      ),
    );
  }
}
