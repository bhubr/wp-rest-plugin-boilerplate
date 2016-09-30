<?php
namespace bhubr;

use Underscore\Underscore as __;

/**
 * Base model to represent WordPress objects
 *
 * As of now, only has two subclasses: PostModel and TermModel.
 * Subclasses implement static methods to perform CRUD operations,
 * using native WordPress per-object-type methods (e.g. wp_insert_post(), ...)
 *
 * Objects are created with some fields as built-in WP type fields (e.g. slug, term_id for terms),
 * others may be:
 *   - all serialized in *one* post or term meta
 *   - put in distinct term metas
 *
 * One exception to the common HTTP method => CRUD operation mapping is the upload of image files.
 * We want to let WP handle the media upload, so that all thumbnails, etc. are created.
 * So we forget about uploading images as base64-encoded strings embedded into the JSON payload
 * sent to the server.
 * We instead use the classic method using $_FILES and $_POST. So we need to distinguish between a
 * "standard JSON payload" (from the Backbone.model.save point of view) and an image payload.
 * We have 3 cases:
 *   1/ Post creation *AND* attachment creation at the same time
 *   2/ Post update and attachment creation
 *   3/ Post update and attachment update
 * All should be done through a POST request, however the ID won't be provided in the first case.
 *   1/ POST /<rest_root>/vidya_portfolio with regular post attrs in POST array and files attrs in FILES
 *   2/ and 3/ POST /<rest_root>/vidya_portfolio/<post_id>
 * Only tricky thing: a post can have *one* featured image and *many* attachments who have this post as
 * parent. In the first case a _thumbnail_id meta_key with the attachment id as value is added to the post
 * In the 2nd case attachments are created
 */
abstract class Base_Model {

    protected static $types = [
        'post' => [],
        'taxonomy' => []
    ];

    protected static $rest_bases = [];
    protected static $rest_classes = [];

    protected static $menu_pos = 40;

    const RELATION_ONE_TO_ONE = 'ONE_TO_ONE';
    const RELATION_ONE_TO_MANY = 'ONE_TO_MANY';
    const RELATION_MANY_TO_MANY = 'MANY_TO_MANY';

    static $cache = [];

    public static function register_type($singular_lc, $name_s, $fields) {
        // $fields = $type_def['fields'];
        $name = \Inflect::pluralize($name_s);
        $plural_lc = \Inflect::pluralize($singular_lc);
        $args = [
            'name' => $name,
            'labels' => [
                'name'               => $name,
                'singular_name'      => $name_s,
                'add_new'            => __("Add", "bhubr-wppc"),
                'add_new_item'       => sprintf(__("Add %s", "bhubr-wppc"), $name_s),
                'edit_item'          => sprintf(__("Edit %s", "bhubr-wppc"), $name_s),
                'new_item'           => sprintf(__("New %s", "bhubr-wppc"), $name_s),
                'all_items'          => sprintf(__("All %s", "bhubr-wppc"), $name_s),
                'view_item'          => sprintf(__("View %s", "bhubr-wppc"), $name_s),
                'search_items'       => sprintf(__("Search %s", "bhubr-wppc"), $name),
                'not_found'          => __("Not found", "bhubr-wppc"),
                'not_found_in_trash' => __("No item found in Trash", "bhubr-wppc"),
                // 'menu_name'          => "$name_s Items", "wp_{$singular_lc}_items"
            ],
            'description'   => "$name_s Items",
            'public'        => true,
            'menu_position' => self::$menu_pos++,
            'supports'      => ['title', 'editor', 'thumbnail'],
            'exclude_from_search' => true
        ];

        self::$types['post'][$singular_lc] = $fields; 
        self::$rest_bases[] = $plural_lc;
        self::$rest_classes[$singular_lc] = '\bhubr\Post_Model';

        register_post_type($singular_lc, $args);
    }

    public static function register_taxonomy($singular_lc, $name_s, $type_lc, $fields) {
        $name = \Inflect::pluralize($name_s);
        $plural_lc = \Inflect::pluralize($singular_lc);
        $args = [
            'labels' => [
                'name' => $name,
                'add_new_item' => sprintf(__("Add %s", "bhubr-wppc"), $name_s),
                // 'new_item_name' => "New $singular_lc Taxonomy",
            ],
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        ];

        self::$types['taxonomy'][$singular_lc] = $fields; 
        self::$rest_bases[] = $plural_lc;
        self::$rest_classes[$singular_lc] = '\bhubr\Term_Model';

        register_taxonomy( $singular_lc, $type_lc, $args );
    }

    protected $object_id = null;
    protected $data;
    static $map_fields;
    static $required_fields;
    //static $extra_fields = null;
    static $skip_fields = array();

    public static function get_types() {
        return self::$types['post'];
    }

    public static function get_type_keys() {
        return array_keys(self::$types['post']);
    }

    public static function get_taxonomies() {
        return self::$types['taxonomy'];
    }

    public static function get_taxonomy_keys() {
        return array_keys(self::$types['taxonomy']);
    }

    public static function get_rest_bases() {
        return self::$rest_bases;
    }

    public static function get_rest_route_class($singular_lc) {
        return self::$rest_classes[$singular_lc];
    }


    /**
     * Get a data field
     */
    public function get( $data_field ) {
        if( array_key_exists($data_field, $this->data)) {
            return $this->data[$data_field];
        }
        else throw new Exception("field $data_field does not exist");
    }

    /**
     * Return as JSON
     */
    public function toJSON() {
        return json_encode($this->data);
    }

    // CRUD OPERATIONS : Create, Read, Update, Get
    // Which one is executed depends on the HTTP request method

    public static function update_object_relations($object, $payload) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rpb_many_to_many';
        // echo __FUNCTION__ . "\n";
        // var_dump($object);
        $object_id = $object['id'];
        $post_fields = self::from_json($payload);
        foreach(static::$relations as $field => $relation_descriptor) {
            if(array_key_exists($field, $payload)) echo "\n #### " . get_called_class() . "::" . __FUNCTION__ . "   =>  FOUND $field in payload\n";
            else continue;

            echo "\n #### " . get_called_class() . "::" . __FUNCTION__ . " " . static::$type . " \n";
            // var_dump($payload[$field]);
            $desc_bits = explode(':', $relation_descriptor);
            $this_rel_class = 'bhubr\\' . $desc_bits[0];
            $this_rel_type = $desc_bits[1];
            $rel_class_relations = $this_rel_class::$relations;
            echo "\n #### " . get_called_class() . "::" . __FUNCTION__ . " " . $this_rel_class::$type . " \n";
            // var_dump($desc_bits);

            // Look for belongs to
            // TODO: REMOVE DUP CODE
            if(array_key_exists(static::$singular, $rel_class_relations)) {
                $reverse_relation_desc = $rel_class_relations[static::$singular];
                $rev_desc_bits = explode(':', $reverse_relation_desc);
                $rev_rel_class = 'bhubr\\' . $rev_desc_bits[0];
                $rev_rel_type = $rev_desc_bits[1];
            }
            if(array_key_exists(static::$plural, $rel_class_relations)) {
                $reverse_relation_desc = $rel_class_relations[static::$plural];
                $rev_desc_bits = explode(':', $reverse_relation_desc);
                $rev_rel_class = 'bhubr\\' . $rev_desc_bits[0];
                $rev_rel_type = $rev_desc_bits[1];
            }
            // var_dump($rev_desc_bits);
            $relation_type = static::get_relation_type($this_rel_type, $rev_rel_type);
            // if ($this_rel_class < $rev_rel_class) {
            //     echo "this $this_rel_class < that $rev_rel_class\n";
            // }
            // else {
            //     echo "this $this_rel_class > that $rev_rel_class\n";

            // }
            $this_first = $this_rel_class > $rev_rel_class;
            switch($relation_type) {
                case self::RELATION_MANY_TO_MANY:
                    // throw new \Exception("Update object relationships: Not implemented for: $relation_type");
                    foreach($payload[$field] as $k => $relatee_id) {
                        if ($this_first) {
                            $data = [
                                'rel_type'   => static::$type . '_' . $this_rel_class::$type,
                                'object1_id' => $object_id,
                                'object2_id' => $relatee_id
                            ];
                        }
                        else {
                            $data = [
                                'rel_type'   => $this_rel_class::$type . '_' . static::$type,
                                'object1_id' => $relatee_id,
                                'object2_id' => $object_id
                            ];
                        }
                        var_dump($data);
                        $wpdb->insert($table_name, $data, ['%s', '%d', '%d']);
                    }
                    break;
                default:
                    throw new \Exception("Update object relationships: Not implemented for: $relation_type");
            }
        }
    }


    public static function create($payload) {
        $object = static::_create(static::$singular, $payload);
        $relations = self::update_object_relations($object, $payload);
        return $object;
    }

    /**
     * Update object
     */
    public static function update($object_id, $payload) {
        return static::_update(static::$singular, $object_id, $payload);
    }


    /**
     * Delete model
     */
    public static function delete($object_id) {
        return static::_delete(static::$singular, $object_id);
    }


    /**
     * Add read model to cache
     */
    public static function add_to_cache($singular, $object) {
        if (! array_key_exists($singular, self::$cache)) self::$cache[$singular] = [];
        self::$cache[$singular][$object['id']] = $object;
    }


    /**
     * Get model from cache
     */
    public static function get_from_cache($singular, $object_id) {
        $is_in_cache = array_key_exists($singular, self::$cache) &&
            array_key_exists($object_id, self::$cache[$singular]);
        return $is_in_cache ? self::$cache[$singular][$object_id] : null;
    }


    /**
     * Read unique model
     */
    public static function read($post_id, $fetch_relations = true) {
        if ($cached_object = self::get_from_cache(static::$singular, $post_id)) {
            $object = $cached_object;
        }
        else {
            $object = static::_read(static::$singular, $post_id);
            self::add_to_cache(static::$singular, $object);
        }
        if (! $fetch_relations) return $object;
// echo "\n#### " . __FUNCTION__ . " $post_id\n";
// var_dump(static::$relations);
        foreach(static::$relations as $field => $relation_descriptor) {
            $object[$field] = self::get_relation($object, $relation_descriptor);
        }
        return $object;
    }


    /**
     * Get relation type from object - related object relation types
     */
    public static function get_relation_type($this_rel_type, $reverse_rel_type) {
        if(
            ($this_rel_type === 'has_one' && $reverse_rel_type === 'belongs_to') ||
            ($reverse_rel_type === 'has_one' && $this_rel_type === 'belongs_to')
        ) {
            return self::RELATION_ONE_TO_ONE;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'belongs_to') {
            return self::RELATION_ONE_TO_MANY;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'has_many') {
            return self::RELATION_MANY_TO_MANY;
        }
        else throw new \Exception("NOT IMPLEMENTED for $this_rel_type, $reverse_rel_type\n");
    }


    /**
     * Get related objects for object and given relation
     */
    public static function get_relation($object, $relation_descriptor) {
        $object_id = $object['id'];
        $desc_bits = explode(':', $relation_descriptor);
        $this_rel_class = 'bhubr\\' . $desc_bits[0];
        $this_rel_type = $desc_bits[1];
        $rel_class_relations = $this_rel_class::$relations;
        // Look for belongs to
        // TODO: REMOVE DUP CODE
        if(array_key_exists(static::$singular, $rel_class_relations)) {
            $reverse_relation_desc = $rel_class_relations[static::$singular];
            $rev_desc_bits = explode(':', $reverse_relation_desc);
            $rev_rel_class = 'bhubr\\' . $rev_desc_bits[0];
            $rev_rel_type = $rev_desc_bits[1];
        }
        if(array_key_exists(static::$plural, $rel_class_relations)) {
            $reverse_relation_desc = $rel_class_relations[static::$plural];
            $rev_desc_bits = explode(':', $reverse_relation_desc);
            $rev_rel_class = 'bhubr\\' . $rev_desc_bits[0];
            $rev_rel_type = $rev_desc_bits[1];
        }
        $relation_type = static::get_relation_type($this_rel_type, $rev_rel_type);
        switch($relation_type) {
            case self::RELATION_ONE_TO_ONE:
                $foreign_key = $this_rel_class::$singular . '_id';
                if(! array_key_exists($foreign_key, $object)) return null;
                return $this_rel_class::read($object[$foreign_key], false);
                break;
            case self::RELATION_ONE_TO_MANY:
                // var_dump('get_relation ' . self::RELATION_ONE_TO_MANY . "\n");
                $primary_key = static::$singular . '_id';
                // echo "primary_key: $primary_key\n";
                $related_objs = $this_rel_class::read_all([
                    'where' => [
                        'field' => $primary_key,
                        'value' => $object['id']
                    ]
                ]);
                // var_dump($related_objs);
                return __::pluck($related_objs, 'id');
                // throw new \Exception("RELATION_ONE_TO_MANY not implemented\n");
                break;
            case self::RELATION_MANY_TO_MANY:
                global $wpdb;
                $table_name = $wpdb->prefix . 'rpb_many_to_many';

                // throw new \Exception("RELATION_MANY_TO_MANY not implemented\n");
                $this_first = $this_rel_class > $rev_rel_class;
                // throw new \Exception("Update object relationships: Not implemented for: $relation_type");
                // foreach($payload[$field] as $k => $relatee_id) {
                    // echo "$v\n";
                    // $data = [
                    //     'rel_type'   => 'post_post',
                    //     'object1_id' => $this_first ? $object_id : $relatee_id,
                    //     'object2_id' => $this_first ? $relatee_id : $object_id
                    // ];
                    // var_dump($object);
                    $where_type = $this_first ? static::$type . '_' . $this_rel_class::$type : $this_rel_class::$type . '_' . static::$type;
                    $where_id = $this_first ? 'object1_id' : 'object2_id';
                    $relatee_id = $this_first ? 'object2_id' : 'object1_id';
                    // var_dump("SELECT * FROM $table_name WHERE rel_type='$where_type' AND $where_id = $object_id");
                    $res = $wpdb->get_results(
                        "SELECT * FROM $table_name WHERE rel_type='$where_type' AND $where_id = $object_id", ARRAY_A
                    );
                    return __::pluck($res, $relatee_id);
                    // var_dump($res);
                // }
                break;
        }

    }


    /**
     * Fetch all objects
     */
    public static function read_all($extra_args = array()) {
        $objects = static::_read_all(static::$singular, $extra_args);
        // echo "\n ### READ_ALL filtering\n";
        // var_dump($objects);
        // var_dump($extra_args);
        if (! $extra_args || ! array_key_exists('where', $extra_args)) return $objects;

        $where = $extra_args['where'];
        // var_dump($where);
        return __::filter($objects, function($item) use($where) {
            return $item[$where['field']] === $where['value'];
        });
    }


    /**
     * Create a model
     */
    //abstract public static function create( $json = null );

    /**
     * Read/fetch a model
     */
    // abstract public function read( $object_id );
 
    /**
     * Update a model
     */
    // abstract public function update( $object_id, $json = null );

    /**
     * Update a model
     */
    // abstract public function delete( $object_id );

    /**
     * Prepare data structure for object creation or update.
     *
     * Called only from post() and put() methods.
     * The data structure can be prepared from a JSON object, if provided.
     * Otherwise the raw PHP request is json-decoded.
     */
    protected static function from_json( $payload ) {
        // Parse json payload
        if(!is_array($payload)) {
            throw new \Exception('Invalid json' );
        }

        // Populate arguments for the insert/update terms functions
        $map_fields = [];
        $fields_done = [];

        // Iterate on built-in fields, mapped if necessary
        foreach( static::$map_fields as $k => $f ) {
            // If key is a string, that means we must map the payload key to the target object key
            // This is because sometimes we map object field name (e.g. post ID or term term_id to id), sometimes not
            $key = is_string( $k ) ? $k : $f;
            if( !array_key_exists( $key, $payload ) ) {
                if( in_array($key, static::$required_fields ) ) { //|| $key === static::$id_key && $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
                    throw new \Exception("Missing required key in data payload: $key");
                }
                else {
                    continue;
                }
            }
            // TODO: payload wrapper that implements Strategy pattern
            $map_fields[$f] = $payload[$key];
            $fields_done[] = $key;
        }

        // Extra fields
        $extra_fields = [];
        foreach( $payload as $k => $field_value ) {
            if( !in_array( $k, static::$skip_fields ) && !in_array($k, $fields_done) ) {
                $extra_fields[$k] = $field_value;
            }
        }
        $map_fields['__meta__'] = $extra_fields;

        return $map_fields;
    }

    
}

?>