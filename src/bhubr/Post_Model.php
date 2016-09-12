<?php
namespace bhubr;

class Post_Model extends Base_Model {

    // Name of the primary object key in Backbone.js app
    static $id_key = 'id';

    // Accepted fields
    static $map_fields = array('id' => 'ID', 'slug' => 'post_name', 'name' => 'post_title'); //, 'content' => 'post_content', 'cat' => 'category', 'status' => 'post_status', 'order' => 'menu_order');
    
    // Terms that are required
    static $required_fields = []; //array('name', 'cat');

    // Fields that should be removed from JSON payload
    static $skip_fields = array('editing', 'success');

    // Taxonomy associated to post type
    static $taxonomy;

    // Name of the meta field
    static $meta_key = '__meta__';

    static $real_metas = array( '_thumbnail_id' );

    /**
     * Private constructor because we don't want an instance to be created if creation fails.
     */
    private function __construct( $data ) {
        // Don't use
        throw new \Exception('Dont use');
        //$this->data = $data;
    }

    public static function init( $post_type ) {
        $taxonomies = get_object_taxonomies( $post_type );
        static::$taxonomy = $taxonomies[0];
        // static::$meta_key = "_{$post_type}_meta";
    }

    /**
     * Create plan
     */
    public static function create( $post_type, $payload ) {

        $base_fields = array(
            'post_type' => $post_type,
            'post_status' => 'publish'
        );

        // Parse JSON payload
        $post_fields = array_merge( $base_fields, self::from_json( $payload ) );
        // Extract meta values and remove them from post fields
        $meta_value = $post_fields['__meta__'];
        unset($post_fields['__meta__']);
        // Create: remove ID field if exists
        unset($post_fields['ID']);

        // Insert post
        $post_id = wp_insert_post( $post_fields, true );

        $post_fields['ID'] = $post_id;
        if( is_wp_error( $post_id ) ) {
            throw new \Exception( 'WP Error: ' . $post_id->get_error_message() );
        }

        if (array_key_exists('term', $post_fields)){
            $terms = self::update_terms($post_id, $post_fields['term']);
        }
        self::update_meta( $post_id, $meta_value );

        // Get the created post from the DB (so we can return the slug if it is different from what was asked)
        $post = get_post($post_id);
        $post_data = self::get_post_fields( $post );

        // Populate values from the meta_value
        $plan_data = array_merge( $post_data, $meta_value ); // , array('cat' => $terms[0]) );

        return  $plan_data;
    }


    public static function update_terms( $post_id, $category) {
        // Set terms
        $terms = wp_set_post_terms( $post_id, array($plan_category), static::$taxonomy, false );
        if( is_wp_error( $terms ) ) {
            throw new \Exception( 'WP Error: ' . $terms->get_error_message() );
        }
        else if( empty( $terms ) ) {
            throw new \Exception("Could not set terms for post $post_id");
        }
        return $terms;
    }

    public static function update_meta( $post_id, $meta_value ) {

        // Update metadata
        $current_meta_value = get_post_meta( $post_id, static::$meta_key, true );

        if( $meta_value === $current_meta_value ) {
            return;
        }
        foreach( static::$real_metas as $meta_key ) {
            if( isset( $meta_value[$meta_key] ) ) {
                $meta_v = $meta_value[$meta_key];
                unset( $meta_value[$meta_key] );
                $success = update_post_meta ( $post_id, $meta_key, $meta_v );
            }
        }
        // If we're here, the submitted meta differs from the current value
        $success = update_post_meta ( $post_id, static::$meta_key, $meta_value );

        if( !$success ) {
            throw new \Exception("Could not update meta for post $post_id");
        }

        return $success[0];
    }


    public static function get_post_fields( $post ) {
        if( is_wp_error( $post ) ) {
            throw new \Exception( 'Object is not a WP_Post' );
        }
        // Populate values from the WP_Post object
        $post_data = array();
        foreach( self::$map_fields as $plan_f => $wp_post_f ) {
            if( !property_exists($post, $wp_post_f) ) continue;
            $post_data[$plan_f] = $post->$wp_post_f;
        }
        return $post_data;
    }


    /**
     * Update plan
     */
    public static function update( $post_type, $post_id, $json = null ) {
        static::init( $post_type );

        if( is_object( $post_id) || intval( $post_id ) === 0 ) {
            throw new \Exception("post_id must be a valid, non-null integer");
        }

        // Parse JSON payload
        $post_fields = self::from_json( $json );
        $post_fields['ID'] = $post_id;
        $meta_value = $post_fields['__meta__'];


        // Update post
        $result = wp_update_post( $post_fields, true );
        if( $result === 0 ) {
            throw new \Exception( "Could not update post $post_id" );
        }

        $terms = self::update_terms_and_meta( $post_id, $post_fields['category'], $meta_value );

        // Get the created post from the DB (so we can return the slug if it is different from what was asked)
        $post = get_post($post_id);

        $post_data = self::get_post_fields( $post );

        // Populate values from the meta_value
        $plan_data = array_merge( $post_data, $meta_value , array('cat' => $terms[0]) );

        return $plan_data;
    }

    /**
     * Delete plan
     */
    public static function delete( $post_type, $post_id ) {
        static::init( $post_type );
        $deleted_post = wp_delete_post( $post_id, true );
        if( false === $deleted_post ) {
            throw new \Exception( "Post $post_id could not be deleted" );
        }
        return $deleted_post;
    }

    /**
     * Fetch plan
     */
    public static function read( $post_type, $post_id ) {
        static::init( $post_type );
        $post = get_post( $post_id );
        if( $post === null ) {
            throw new \Exception("Post with id=$post_id was not found");
        }
        $post_data = self::get_post_fields( $post );
        // $terms = wp_get_post_terms( $post_id, static::$taxonomy );
        // if( is_wp_error( $terms ) ) {
        //     throw new \Exception( 'WP Error: ' . $terms->get_error_message() );
        // }
        $meta_value = get_post_meta( $post_id, static::$meta_key, true );
        $thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
        if( $thumb_id ) {
            $meta_value['_thumbnail_src'] = wp_get_attachment_thumb_url( $thumb_id );
        }
        $data = array_merge( $post_data, $meta_value ? $meta_value : array() ); //, array('cat' => $terms[0]->term_id) );
        return $data;
        //return new PortfolioModel( $plan_data );
    }

    /**
     * Fetch all
     */
    public static function read_all( $post_type, $extra_args = array() ) {
        static::init( $post_type );
        $ret = array();
        $args = array('post_type' => $post_type, 'posts_per_page' => -1);
        if( array_key_exists('term', $extra_args ) ) {
            $args[static::$taxonomy] = $extra_args['term'];
            unset($extra_args['term']);
        }
        $posts = get_posts( array_merge( $args, $extra_args ) );
        foreach( $posts as $post ) {
            $post_data = self::get_post_fields( $post );
            $terms = wp_get_post_terms( $post->ID, static::$taxonomy );
            if( is_wp_error( $terms ) ) {
                throw new \Exception( 'WP Error: ' . $terms->get_error_message() );
            }
            $meta_value = get_post_meta( $post->ID, static::$meta_key, true );
            $thumb_id = get_post_meta( $post->ID, '_thumbnail_id', true );
            if( $thumb_id ) {
                $meta_value['_thumbnail_src'] = wp_get_attachment_thumb_url( $thumb_id );
            }
            $post_data = array_merge( $post_data, $meta_value ? $meta_value : array() );
            if( !empty( $terms ) ) {
                $post_data['cat'] = $terms[0]->term_id;
            }
            $ret[] = $post_data;
        }
        return $ret;
    }
}
    