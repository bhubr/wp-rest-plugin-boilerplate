<?php
namespace bhubr\REST\Model;

use Underscore\Underscore as __;
use bhubr\REST\Payload\Formatter;

class Post extends Base implements Methods {

    const ID_KEY = 'ID';

    // Name of the primary object key in Backbone.js app
    static $id_key = 'id';

    // Accepted fields
    private   static $map_fields_core_in = ['post_name' => 'slug'];
    private   static $map_fields_core_out = ['ID' => 'id', 'post_name' => 'slug'];
    protected static $map_fields = ['post_title' => 'name'];
    //, 'content' => 'post_content', 'cat' => 'category', 'status' => 'post_status', 'order' => 'menu_order');
    
    // Terms that are required
    static $required_fields = []; //array('name', 'cat');

    // Fields that should be removed from JSON payload
    static $skip_fields = array('editing', 'success');

    // Taxonomy associated to post type
    static $taxonomy;

    protected static $types = [];


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
        $registered_post_types = static::$types;
        // var_dump($registered_post_types);
        if (array_search($post_type, $registered_post_types) === false) {
            $msg = sprintf("Unknown post type: %s (registered types: %s)", $post_type, implode(', ', $registered_post_types));
            throw new \Exception($msg);
        }

        static::$type_fields = static::get_models_keys(); // Base_Model::get_types()[$post_type];
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
                if (!is_int($v)) throw new \Exception("Value for singular $k ID should be an integer");
                $post_fields['__terms__'][$k] = (int)$v;
                unset($post_fields['__meta__'][$k]);
            }
            else if (($pos_in_tax_p = array_search($k, $taxonomies_p)) !== false) {
                $tax_name_s = $taxonomies_s[$pos_in_tax_p];
                if (!is_array($v)) throw new \Exception("Value for plural $tax_name_s IDs should be an array of integers");
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
     * Create WP Custom Post
     * @param $post_type string Registered WP Custom Post Type
     */
    public static function _create( $post_type, $payload ) {
        echo "\n\n\n##### DESCRIPTOR FOR $post_type:\n";
        $model_descriptor = Registry::get_instance()->get_model_by('singular_lc', $post_type);
        // var_dump(static::$descriptor);
        $parsed_payload = Formatter::process_payload( $payload, $model_descriptor );
        var_dump($parsed_payload);
        $payload = $parsed_payload['attributes'];
        static::init( $post_type );

        $base_fields = array(
            'post_type' => $post_type,
            'post_status' => 'publish'
        );

        $split_payload = self::map_fields_payload_to_wp($payload);
        $post_fields = array_merge( $base_fields, $split_payload->get_f('wp_obj'));
        $meta_value = $split_payload->get_f('_meta_');

        // Insert post
        $post_id = wp_insert_post( $post_fields, true );
        if( $wp_error = is_wp_error( $post_id ) ) {
            throw new \Exception( 'WP Error: ' . $post_id->get_error_message() );
        }

        $post_fields['ID'] = $post_id;
        // self::update_terms( $post_id, $post_fields );
        self::update_meta( $post_id, $meta_value );

        // Get the created post from the DB (so we can return the slug if it is different from what was asked)
        $post = get_post($post_id);
        $post_data = self::map_fields_wp_to_obj( $post );
        // $post_terms = self::get_object_terms($post_type, $post_id);

        // Populate values from the meta_value
        return array_merge( $post_data, $meta_value ); // , $post_terms );
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
                // else if( empty( $terms ) ) {
                //     throw new \Exception("Could not set terms for post $post_id");
                // }

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
        if (! empty($current_meta_value)) {
            foreach($current_meta_value as $key => $val) {
                if(!array_key_exists($key, $meta_value)) {
                    $meta_value[$key] = $val;
                }
            }
        }
        // If we're here, the submitted meta differs from the current value
        $success = update_post_meta ( $post_id, static::$meta_key, $meta_value );

        if( !$success ) {
            throw new \Exception("Could not update meta for post $post_id");
        }

        return $success[0];
    }

    public static function get_core_fields_in() {
        return self::$map_fields_core_in;
    }

    public static function get_core_fields_out() {
        return self::$map_fields_core_out;
    }


    public static function map_fields_wp_to_obj( $post ) {
        $map_fields = array_merge(self::get_core_fields_out(), static::$map_fields);
        if( is_wp_error( $post ) ) {
            throw new \Exception( 'Object is not a WP_Post' );
        }
        // Populate values from the WP_Post object
        $post_data = array();
        foreach( $map_fields as $wp_post_f => $obj_key ) {
            if( !property_exists($post, $wp_post_f) ) throw new \Exception("Property WP_Post::$wp_post_f not found");
            $post_data[$obj_key] = $post->$wp_post_f;
        }
        return $post_data;
    }

    /**
     * Internal update object
     */
    public static function _update( $post_type, $post_id, $payload ) {
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

        $post_data = self::map_fields_wp_to_obj( $post );
        $post_terms = self::get_object_terms($post_type, $post_id);

        // Populate values from the meta_value
        $plan_data = array_merge( $post_data, $meta_value, $post_terms );

        return $plan_data;
    }


    /**
     * Internal delete model
     */
    public static function _delete( $post_type, $post_id ) {
        static::init( $post_type );
        $deleted_post = wp_delete_post( $post_id, true );
        if( false === $deleted_post ) {
            throw new \Exception( "Post $post_id could not be deleted" );
        }
        return (array)$deleted_post;
    }


    /**
     * Fetch object
     */
    public static function _read( $post_type, $post_id ) {
        static::init( $post_type );
        $post = get_post( $post_id );
        if( $post === null ) {
            throw new \Exception("Post with id=$post_id was not found");
        }
        $post_data = self::map_fields_wp_to_obj( $post );
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
     * Internal fetch all
     */
    public static function _read_all( $post_type, $extra_args = array() ) {
        static::init( $post_type );
        $ret = array();
        // try {
        //     echo "\n #### " . __FUNCTION__ . "#1\n";
        //     throw new \Exception("\n #### " . __FUNCTION__ . "#1\n");
        // } catch(\Exception $e) {
        //     $index = 0;
        //     array_map(function($item) use($index) {
        //         // var_dump($item);
        //         if (!isset($item['file'])) return;
        //         echo $index++ . " " . $item['file'] . " " . $item['line'] . "\n";
        //     }, $e->getTrace());
        // }
        
        $args = array('post_type' => $post_type, 'posts_per_page' => -1, 'order' => 'ASC');
        if( array_key_exists('term', $extra_args ) ) {
            $args[static::$taxonomy] = $extra_args['term'];
            unset($extra_args['term']);
        }
        $posts = get_posts( array_merge( $args, $extra_args ) );
        foreach( $posts as $post ) {
            $post_data = self::map_fields_wp_to_obj( $post );
            $post_terms = self::get_object_terms($post_type, $post->ID);
            $meta_value = get_post_meta( $post->ID, static::$meta_key, true );
            $thumb_id = get_post_meta( $post->ID, '_thumbnail_id', true );
            if( $thumb_id ) {
                $meta_value['_thumbnail_src'] = wp_get_attachment_thumb_url( $thumb_id );
            }
            $post_data = array_merge( $post_data, $meta_value ? $meta_value : array(), $post_terms );
            $ret[] = $post_data;
        }
        // echo "\n #### " . __FUNCTION__ . "#5b\n";
        return $ret;
    }
}
    