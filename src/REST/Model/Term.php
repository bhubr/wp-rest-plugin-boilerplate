<?php
namespace bhubr\REST\Model;

class Term extends Base implements Methods {

    const ID_KEY = 'term_id';


    static $id_key = 'term_id';
    // Accepted fields
    static $map_fields = array('term_id' => 'id', 'slug', 'name', 'description');
    static $required_fields = []; //array('name');

    // Extra fields (encoded as JSON into post_content)
    //static $extra_fields = array('price', 'currency', 'description');
    static $skip_fields = array('editing', 'success', 'selected');

    // Taxonomy associated to term model
    static $taxonomy;

    // Name of the meta field
    static $meta_key;

    private function __construct( $data ) {
        $this->data = $data;
    }

    public static function init( $taxonomy ) {
        static::$taxonomy = $taxonomy;
        static::$meta_key = "_{$taxonomy}_meta";
    }

    /**
     * Get next available slug if the required one exists
     */
    public static function get_slug( $slug, $term_id = 0 ) {
        $existing_term = get_term_by('slug', $slug, static::$taxonomy);
        if( $existing_term !== false && ( $term_id === 0 || $term_id !== $existing_term->term_id ) ) {
            $slug_suffix_n = 1;
            do {
                $next_term_slug = $existing_term->slug . '-' . ++$slug_suffix_n;
            }
            while( get_term_by('slug', $next_term_slug, static::$taxonomy) !== false );
            $slug .= '-' . $slug_suffix_n;
        }
        return $slug;
    }

    /**
     * Create cat
     */
    public static function _create( $taxonomy, $payload = null ) {

        self::init( $taxonomy );

        // Parse JSON payload
        $term_fields = self::from_json( $payload );

        $name = array_key_exists('name', $term_fields) ? $term_fields['name'] : $taxonomy . "-term-" . base_convert(time(), 10, 36);
        // regex for slug with suffix: '/[\d\w\-]+\-(\d+)$/'
        if( !array_key_exists('slug', $term_fields) ) {
            $term_fields['slug'] = sanitize_title_with_dashes($name);
        }

        $term_fields['slug'] = static::get_slug( $term_fields['slug'] );
        $meta_value = $term_fields['__meta__'];
        unset($term_fields['__meta__']);
        unset($term_fields['term_id']);
        
        // Insert term
        $term_id = wp_insert_term( $name, $taxonomy, $term_fields );
        if( is_wp_error( $term_id ) ) {
            throw new \Exception( 'WP Error: ' . $term_id->get_error_message() );
        }
        $term_id = $term_id['term_id'];
        $success = add_term_meta ($term_id, '__meta__', $meta_value, true);
        if( !$success ) {
            throw new \Exception("Could not add meta for term $term_id");
        }

        $term_fields['id'] = $term_id;
        $term_fields = array_merge( $term_fields, $meta_value );
        return $term_fields;
    }

    /**
     * Update cat
     */
    public static function _update( $taxonomy, $term_id, $json = null ) {

        self::init( $taxonomy );

        // Parse JSON payload
        if( is_object( $term_id) || intval( $term_id ) === 0 ) {
            throw new \Exception("term_id must be a valid, non-null integer");
        }

        $term_fields = self::from_json( $json );
        if(array_key_exists('slug', $term_fields)) {
            $term_fields['slug'] = static::get_slug( $term_fields['slug'], $term_id );
        }
        
        $meta_value = $term_fields['__meta__'];
        unset($term_fields['__meta__']);

        $existing_meta = get_term_meta($term_id, '__meta__', true);
        if( $existing_meta !== $meta_value ) {
            $success = update_term_meta (  $term_id, '__meta__', $meta_value );
            if( !$success ) {
                throw new \Exception("Could not update meta for term $term_id");
            }

        }

        // Update term
        $result = wp_update_term( $term_id, $taxonomy, $term_fields );
        if( is_wp_error( $result ) ) {
            throw new \Exception( 'WP Error: ' . $result->get_error_message() );
        }

        $term_fields['id'] = $term_id;

        // Overwrite old data
        return array_merge( $term_fields, $meta_value );
    }

    /**
     * Delete cat
     */
    public static function _delete( $taxonomy, $term_id ) {
        self::init( $taxonomy );

        $result = wp_delete_term( $term_id, static::$taxonomy );
        if( is_wp_error( $result ) ) {
            throw new \Exception( 'WP Error: ' . $result->get_error_message() );
        }
        else if( false === $result ) {
            throw new \Exception( "Term $term_id not found" );
        }
        return $result;
    }

    /**
     * Get cat
     */
    public static function _read( $taxonomy, $term_id ) {
        self::init( $taxonomy );
        $term = get_term_by( 'term_id', $term_id, static::$taxonomy );
        if( $term === false ) {
            throw new \Exception("term with id=$term_id was not found");
        }
        $meta_value = get_term_meta($term_id, '__meta__', true);
        if( empty( $meta_value ) ) {
            $meta_value = array();
        }
        $term_fields = array(
            'id' => $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
            'description' => $term->description
        );
        return array_merge( $term_fields, $meta_value ? $meta_value : array() );
    }
    /**
     * Get cat
     */
    public static function _read_all( $taxonomy, $extra_args = array() ) {
        self::init( $taxonomy );
        $hide_empty = array_key_exists('hide_empty', $extra_args) && $extra_args['hide_empty'] === true;
        $terms = get_terms( $taxonomy, array( 'hide_empty' => $hide_empty ) );
        if( is_wp_error( $terms ) ) {
            throw new \Exception("No terms where found in this taxonomy: $taxonomy");
        }
        $terms_with_metas = array();
        foreach( $terms as $term ) {
            $meta_value = get_term_meta($term->term_id, '__meta__', true);
            $term_fields = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
                'description' => $term->description
            );
            $terms_with_metas[] = array_merge( $term_fields, $meta_value ? $meta_value : array() );
        }
        return $terms_with_metas;
    }
}
