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

    static $type_fields = null;
    static $taxonomies = null;

    // Name of the meta field
    static $meta_key = '__meta__';

    static $real_metas = array( '_thumbnail_id' );

    /**
     * Private constructor because we don't want an instance to be created if creation fails.
     */
    private function __construct( $data ) {
        // Don't use
        throw new \Exception('Dont use');
    }

    public static function init( $post_type ) {
        $registered_post_types = self::get_type_keys();
        if (array_search($post_type, $registered_post_types) === false) {
            $msg = sprintf("Unknown post type: %s (registered types: %s)", $post_type, implode(', ', $registered_post_types));
            throw new Model_Exception($msg);
        }

        static::$type_fields = Base_Model::get_types()[$post_type];
        static::$taxonomies = get_object_taxonomies( $post_type );
    }

    public static function extract_payload_taxonomies($post_type, $payload) {
        $post_fields = self::from_json($payload);
        $post_fields['__terms__'] = [];
        $taxonomies_s = get_object_taxonomies($post_type);
        $taxonomies_p = array_map(function($tax_name_s) {
            return \Inflect::pluralize($tax_name_s);
        }, $taxonomies_s);
        foreach($post_fields['__meta__'] as $k => $v) {
            if (($pos_in_tax_s = array_search($k, $taxonomies_s)) !== false) {
                if (!is_int($v)) throw new Model_Exception("Value for singular $k ID should be an integer");
                $post_fields['__terms__'][$k] = (int)$v;
                unset($post_fields['__meta__'][$k]);
            }
            else if (($pos_in_tax_p = array_search($k, $taxonomies_p)) !== false) {
                $tax_name_s = $taxonomies_s[$pos_in_tax_p];
                if (!is_array($v)) throw new Model_Exception("Value for plural $tax_name_s IDs should be an array of integers");
                $post_fields['__terms__'][$tax_name_s] = array_map(function($v_cast) {
                    return (int)$v_cast;
                }, $v);
                unset($post_fields['__meta__'][$k]);
            }
        }
        return $post_fields;
    }

    public static function get_object_terms($post_type, $post_id) {
        $terms = [];
        $taxonomies_p = array_map(function($tax_name_s) {
            return \Inflect::pluralize($tax_name_s);
        }, static::$taxonomies);
        foreach(static::$type_fields as $field) {
            if (($pos_in_tax_s = array_search($field, static::$taxonomies)) !== false) {
                $post_terms = wp_get_object_terms( $post_id, $field );
                if (! empty($post_terms)) {
                    $unique_term = array_pop($post_terms);
                    $terms[$field] = $unique_term->term_id;
                }
                else $terms[$field] = null;
            }
            else if (($pos_in_tax_p = array_search($field, $taxonomies_p)) !== false) {
                $tax_name_s = static::$taxonomies[$pos_in_tax_p];
                $post_terms = wp_get_object_terms( $post_id, $tax_name_s );
                $terms[$field] = !empty($post_terms) ? array_map(function($term) { return $term->term_id; }, $post_terms) : [];
            }
        }
        return $terms;
    }

    // public static function delete_object_terms($post_type, $post_id) {
    //     $terms = [];
    //     $post_terms = null;
    //     $taxonomies_p = array_map(function($tax_name_s) {
    //         return \Inflect::pluralize($tax_name_s);
    //     }, static::$taxonomies);
    //     foreach(static::$type_fields as $field) {
    //         echo "$post_id $field\n";
    //         if (($pos_in_tax_s = array_search($field, static::$taxonomies)) !== false) {
    //             echo "SINGLE $field\n";
    //             $post_terms = wp_get_object_terms( $post_id, $field );
    //             var_dump($post_terms);
    //             if (empty($post_terms)) continue;
    //         }
    //         else if (($pos_in_tax_p = array_search($field, $taxonomies_p)) !== false) {
    //             $tax_name_s = static::$taxonomies[$pos_in_tax_p];
    //             echo "PLURAL $field => $tax_name_s\n";
    //             $post_terms = wp_get_object_terms( $post_id, $tax_name_s );
    //             var_dump($post_terms);
    //         }
    //         else continue;
    //         $term_ids = array_map(function($term) { return $term->term_id; }, $post_terms);
    //         wp_remove_object_terms($post_id, $term_ids, $field);

    //     }
    //     return $terms;
    // }

    /**
     * Create plan
     */
    public static function create( $post_type, $payload ) {
        static::init( $post_type );

        $base_fields = array(
            'post_type' => $post_type,
            'post_status' => 'publish'
        );

        // Parse JSON payload
        $post_fields = array_merge( $base_fields, self::extract_payload_taxonomies($post_type, $payload) );
        // Extract meta values, remove them and ID from post fields
        $meta_value = $post_fields['__meta__'];
        unset($post_fields['__meta__']);
        unset($post_fields['ID']);

        // Insert post
        $post_id = wp_insert_post( $post_fields, true );
        if( $wp_error = is_wp_error( $post_id ) ) {
            throw new \Exception( 'WP Error: ' . $post_id->get_error_message() );
        }

        $post_fields['ID'] = $post_id;
        self::update_terms( $post_id, $post_fields );
        self::update_meta( $post_id, $meta_value );

        // Get the created post from the DB (so we can return the slug if it is different from what was asked)
        $post = get_post($post_id);
        $post_data = self::get_post_fields( $post );
        $post_terms = self::get_object_terms($post_type, $post_id);

        // Populate values from the meta_value
        $model = array_merge( $post_data, $meta_value, $post_terms );

        return $model;
    }


    public static function update_terms( $post_id, $post_fields) {
        $output_terms = [];
        if (array_key_exists('__terms__', $post_fields)) {
            $term_data = $post_fields['__terms__'];
            unset($post_fields['__terms__']);
            foreach($term_data as $taxonomy => $term_ids) {
                // Set terms
                $terms = wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
                if( is_wp_error( $terms ) ) {
                    throw new \Exception( 'WP Error: ' . $terms->get_error_message() );
                }
                else if( empty( $terms ) ) {
                    throw new \Exception("Could not set terms for post $post_id");
                }

                $output_terms[$taxonomy] = $terms;
            }
        }
        return $output_terms;
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
    public static function update( $post_type, $post_id, $payload ) {
        static::init( $post_type );

        if( is_object( $post_id) || intval( $post_id ) === 0 ) {
            throw new \Exception("post_id must be a valid, non-null integer");
        }

        // Parse JSON payload
        $post_fields = self::extract_payload_taxonomies($post_type, $payload);
        $post_fields['ID'] = $post_id;
        $meta_value = $post_fields['__meta__'];


        // Update post
        $result = wp_update_post( $post_fields, true );
        if( $result === 0 ) {
            throw new \Exception( "Could not update post $post_id" );
        }

        // $terms = self::update_terms_and_meta( $post_id, $post_fields['category'], $meta_value );
        self::update_terms( $post_id, $post_fields );
        self::update_meta( $post_id, $meta_value );

        // Get the created post from the DB (so we can return the slug if it is different from what was asked)
        $post = get_post($post_id);

        $post_data = self::get_post_fields( $post );
        $post_terms = self::get_object_terms($post_type, $post_id);

        // Populate values from the meta_value
        $plan_data = array_merge( $post_data, $meta_value, $post_terms );

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
        return (array)$deleted_post;
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
        $post_terms = self::get_object_terms($post_type, $post_id);

        $meta_value = get_post_meta( $post_id, static::$meta_key, true );
        $thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
        if( $thumb_id ) {
            $meta_value['_thumbnail_src'] = wp_get_attachment_thumb_url( $thumb_id );
        }
        $data = array_merge( $post_data, $meta_value ? $meta_value : array(), $post_terms );
        return $data;
        //return new PortfolioModel( $plan_data );
    }

    /**
     * Fetch all
     */
    public static function read_all( $post_type, $extra_args = array() ) {
        static::init( $post_type );
        $ret = array();
        $args = array('post_type' => $post_type, 'posts_per_page' => -1, 'order' => 'ASC');
        if( array_key_exists('term', $extra_args ) ) {
            $args[static::$taxonomy] = $extra_args['term'];
            unset($extra_args['term']);
        }
        $posts = get_posts( array_merge( $args, $extra_args ) );
        foreach( $posts as $post ) {
            $post_data = self::get_post_fields( $post );
            $post_terms = self::get_object_terms($post_type, $post->ID);
            $meta_value = get_post_meta( $post->ID, static::$meta_key, true );
            $thumb_id = get_post_meta( $post->ID, '_thumbnail_id', true );
            if( $thumb_id ) {
                $meta_value['_thumbnail_src'] = wp_get_attachment_thumb_url( $thumb_id );
            }
            $post_data = array_merge( $post_data, $meta_value ? $meta_value : array(), $post_terms );
            $ret[] = $post_data;
        }
        return $ret;
    }
}
    