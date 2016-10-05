<?php
 /**
  * @package bhubr\REST
  */
namespace bhubr\REST\Model;

use bhubr\REST\Utils\Collection;
use bhubr\REST\Utils\Tracer;

/**
 * Holds model data for all the registered plugins.
 * Registers WP custom post types and taxonomies
 */
class Registry {

    /**
     * Holds information on each model
     */
    public $registry;

    /**
     * Pivot table name
     */
    private $pivot_table;

    /**
     * Relation types
     */
    const RELATION_ONE_TO_ONE = 'ONE_TO_ONE';
    const RELATION_ONE_TO_MANY = 'ONE_TO_MANY';
    const RELATION_MANY_TO_ONE = 'MANY_TO_ONE';
    const RELATION_MANY_TO_MANY = 'MANY_TO_MANY';

    protected $type_class_map = [
        'post' => 'bhubr\Post_Model',
        'term' => 'bhubr\Term_Model'
    ];

    /**
     * Class instance
     */
    private static $_instance;

    /**
     * Get unique class instance
     */
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Registry();
        }
        return self::$_instance;
    }


    /**
     * Private constructor
     */
    private function __construct() {
        global $wpdb;
        $this->pivot_table = $wpdb->prefix . 'rpb_many_to_many';
        $this->registry = new Collection();
    }


    /**
     * Load then register models
     */
    public function load_and_register_models($plugin_descriptor) {
        $model_files = $this->get_model_files($plugin_descriptor);
        $model_descriptors = new Collection;
        foreach($model_files as $file) {
            $class_name = $this->load_model_file($file, $plugin_descriptor);
            $plural_lc = $class_name::$plural;
            $model_descriptors[$plural_lc] = $this->add_model($class_name, $plugin_descriptor);
        }
        $model_descriptors->each( [$this, 'parse_model_relationships'] );
        $model_descriptors->each( [$this, 'model_relationships_set_strategies'] );
        // var_dump($this->registry);
        $this->register_models_to_wordpress();
    }


    /**
     * Scan the model folder to retrieve model file names
     */
    protected function get_model_files($plugin_descriptor) {
        $models_dir  = $plugin_descriptor['plugin_dir'] . DIRECTORY_SEPARATOR . $plugin_descriptor['models_dir'];
        $plugin_name = $plugin_descriptor['plugin_name'];
        if (! file_exists($models_dir)) {
            throw new \Exception("Error for plugin $plugin_name: models dir $models_dir doesn't exist");
        }
        return glob("$models_dir/*.php");
    }


    /**
     * Load an individual model class
     */
    protected function load_model_file($file, $plugin_descriptor) {
        require_once $file;
        $class_name = $plugin_descriptor['models_namespace'] . basename($file, '.php');
        if (! class_exists($class_name)) {
            $file_name = $plugin_descriptor['models_dir'] . '/' . basename($file);
            throw new \Exception("Class $class_name not found in $file_name. Check namespacing in file => " . $plugin_descriptor['models_namespace']);
        }
        $required_properties = ['type', 'singular', 'plural', 'name_s', 'name_p', 'fields', 'relations'];
        $missing_properties = [];
        foreach($required_properties as $prop) {
            if(! property_exists($class_name, $prop)) $missing_properties[] = $prop;
        }
        if($class_name::$type === 'term' && ! property_exists($class_name, 'post_type')) {
            $missing_properties[] = 'post_type';
        }
        if (count($missing_properties)) {
            $missing_str = implode(', ', $missing_properties);
            throw new \Exception("Missing required properties: [$missing_str] in $class_name");
        }
        return $class_name;
    }


    /**
     * Prepare data for use in REST controller
     */
    protected function add_model($class_name, $plugin_descriptor) {
        $plural_lc = $class_name::$plural;
        // Tracer::save(__CLASS__, __FUNCTION__);
        if ($this->registry->has($plural_lc)) {
            throw new \Exception(
                "Cannot register duplicate model {$class_name::$singular} in registry"
                );
        }

        $registry = $this->registry->put( $plural_lc, collect_f ( [
            'type'          => $class_name::$type,
            'singular_lc'   => $class_name::$singular,
            'namespace'     => $plugin_descriptor['rest_root'] .
                               '/v' . $plugin_descriptor['rest_version'],
            'rest_type'     => $plugin_descriptor['rest_type'],
            'class'         => $class_name,
            // 'relationships' => $this->parse_model_relationships(
            //     collect_f($class_name::$relations)
            // )
            '_relationships' => collect_f($class_name::$relations)
        ] ) );
        return $registry->get($plural_lc);
    }


    /**
     * Fetch data for rest controller
     */
    public function get_model($plural_lc) {
        return $this->registry->get_f($plural_lc, "Model plural/lowercase key '$plural_lc' not found in registry");
    }


    /**
     * Get class for given route's plural/lowercase model name
     */
    public function get_model_class($plural_lc) {
        return $this->get_model($plural_lc)->get_f('class');
    }


    /**
     * Get rest keys
     */
    public function get_models_keys() {
        return $this->registry->keys()->toArray();
    }


    /**
     * Register loaded models
     */
    protected function register_models_to_wordpress() {
        $registry_values = $this->registry->values();
        $types = $registry_values->groupBy('type')->toArray();
        if (array_key_exists('post', $types)) {
            foreach ($types['post'] as $descriptor) {
                Post::register_model_key($descriptor['singular_lc']);
                $this->register_wp_post_type($descriptor['class']);
            }
        }
        if (array_key_exists('term', $types)) {
            foreach ($types['term'] as $descriptor) {
                Term::register_model_key($descriptor['singular_lc']);
                $this->register_wp_taxonomy($descriptor['class']);
            }
        }
    }


    /**
     * Register a WordPress custom post type
     */
    protected function register_wp_post_type($class_name) {
        $singular_lc = $class_name::$singular;
        $plural_lc   = $class_name::$plural;
        $name_s      = $class_name::$name_s;
        $name_p      = $class_name::$name_p;
        // $fields      = array_keys($class_name::$fields);

        $args = [
            'name' => $name_p,
            'labels' => [
                'name'               => $name_p,
                'singular_name'      => $name_s,
                'add_new'            => __("Add", "bhubr-wprbp"),
                'add_new_item'       => sprintf(__("Add %s", "bhubr-wprbp"), $name_s),
                'edit_item'          => sprintf(__("Edit %s", "bhubr-wprbp"), $name_s),
                'new_item'           => sprintf(__("New %s", "bhubr-wprbp"), $name_s),
                'all_items'          => sprintf(__("All %s", "bhubr-wprbp"), $name_s),
                'view_item'          => sprintf(__("View %s", "bhubr-wprbp"), $name_s),
                'search_items'       => sprintf(__("Search %s", "bhubr-wprbp"), $name_p),
                'not_found'          => __("Not found", "bhubr-wprbp"),
                'not_found_in_trash' => __("No item found in Trash", "bhubr-wprbp"),
            ],
            'description'   => "$name_s Items",
            'public'        => true,
            // 'menu_position' => $this->menu_pos++,
            'supports'      => ['title', 'editor', 'thumbnail'],
            'exclude_from_search' => true
        ];

        // $this->types['post'][$singular_lc] = $fields; 
        // $this->rest_bases[] = $plural_lc;
        // $this->rest_classes[$plural_lc] = $class_name;

        // Allow wrapping native WP classes with objects... not sure it's a good idea...
        $wp_types_kv = get_post_types(['_builtin' => true]);
        $wp_types = array_keys($wp_types_kv);
        if (array_search($singular_lc, $wp_types) !== false) return;

        register_post_type($singular_lc, $args);
    }


    /**
     * Register a WordPress custom taxonomy
     */
    protected function register_wp_taxonomy($class_name) {
        $type_lc     = $class_name::$post_type;
        $singular_lc = $class_name::$singular;
        $plural_lc   = $class_name::$plural;
        $name_s      = $class_name::$name_s;
        $name_p      = $class_name::$name_p;

        // $fields      = array_keys($class_name::$fields);
        // $name = \Inflect::pluralize($name_s);
        // $plural_lc = \Inflect::pluralize($singular_lc);
        $args = [
            'labels' => [
                'name' => $name_p,
                'singular_name' => $name_s,
                'new_item_name' => sprintf(__("New %s", "bhubr-wprbp"), $name_s),
                'add_new_item'  => sprintf(__("Add %s", "bhubr-wprbp"), $name_s),
                'view_item'     => sprintf(__("View %s", "bhubr-wprbp"), $name_s),
                'search_items'  => sprintf(__("Search %s", "bhubr-wprbp"), $name_p),
                'not_found'     => __("Not found", "bhubr-wprbp"),
            ],
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        ];

        // $this->types['taxonomy'][$singular_lc] = $fields; 
        // $this->rest_bases[] = $plural_lc;
        // $this->rest_classes[$plural_lc] = $class_name;
        register_taxonomy( $singular_lc, $type_lc, $args );
    }

    public function parse_model_relationships($model_descriptor) {
        $raw_relationships = $model_descriptor->get('_relationships');
        $relationships = $raw_relationships->map([$this, 'parse_relationship']);
        $model_descriptor->put('relationships', $relationships);
    }

    public function parse_relationship($relationship_descriptor) {
        $desc_bits = explode(':', $relationship_descriptor);
        var_dump($desc_bits);
        $rel_class = $desc_bits[0];
        $rel_type = $desc_bits[1];
        $output = [
            'type'     => $rel_class::$plural,
            'type_s'   => $rel_class::$singular,
            'plural'   => array_search($rel_type, ['has_one', 'belongs_to']) === false,
            'rel_type' => $desc_bits[1],
        ];
        if( count( $desc_bits ) > 2 ) $output['inverse'] = $desc_bits[2];
        return collect_f($output);
    }

    public function model_relationships_set_strategies($model_descriptor, $model_plural) {
        $relationships = $model_descriptor->get_f('relationships');
        echo "#### Set strategies for model $model_plural #### \n";
        var_dump($relationships);
        $relationships->each(
            function( $relationship, $key ) use( $model_descriptor, $model_plural ) {
                $this->set_strategies( $relationship, $model_descriptor, $model_plural, $key );
            }
        );
    }

    public function set_strategies( $relationship, $model_descriptor, $model_plural, $key ) {
        echo "#### Set strategy for model $model_plural, key $key #### \n";
        var_dump($relationship);

        $relatee_type    = $relationship->get_f('type');
        $inverse_rel_key = $relationship->get_f('inverse');
        $inverse_desc    = $this->registry->get_f( $relatee_type );
        $inverse_rel     = $inverse_desc->get('relationships')->get($inverse_rel_key);
        var_dump($inverse_rel);
        // $inverse_rel_key = $relationship->get_f('inverse');
        // $inverse_rel = $relatee_desc->get_f($inverse_rel_key);
        // foreach ($relatee_desc->get('relationships') as $key => $_relationship) {
        //     if ( $_relationship->get( 'type' ) !== $model_plural ) continue;
        //     $inverse_rel = $_relationship;
        //     $inverse_rel_key = $key;
        // }
        //     function( $relationship, $key ) use( $model_plural, &$inverse_rel_key ) {
        //         $inverse_rel_key = $key;
        //         if ($relationship->get( 'type' ) === $model_plural) {

        //         } 
        //         return 
        //     }
        // );
        printf("(%s) %s: %s:%s => ", 
            $model_plural, $key, $relationship->get('type'), $relationship->get('rel_type')
            );
        printf("(%s) %s : %s:%s\n",
            $relatee_type, $inverse_rel_key, $inverse_rel->get('type'), $inverse_rel->get('rel_type'));

        $combined_rel_type = $this->get_relation_type(
            $relationship->get_f('rel_type'),
            $inverse_rel->get_f('rel_type')
        );
        $create_or_update_func = $this->get_func(
            'GET',
            $relationship->get_f('rel_type'),
            $inverse_rel->get_f('rel_type')
        );
        $create_or_update_func_args = [
            $model_descriptor->get_f('singular_lc'),
            $inverse_desc->get_f('singular_lc')
        ];
        var_dump($create_or_update_func);
        var_dump($create_or_update_func_args);
        echo $combined_rel_type . "\n";
        echo "\n-----------\n\n";
        // var_dump($model_plural . ' ' . $relationship->get('rel_type') . ' => ' .
        //  $inverse_rel->get('type') . ' ' . $inverse_rel->get('rel_type') );
    }

    public function get_inverse_relationship($relationship) {
        $relatee_type = $relationship->get_f('type');
        return $this->registry->get_f( $relatee_type )->get_f('relationships');
    }

    public function get_route_function($method, $relationship) {
        $reverse_key = $relationship->get('inverse');
        $reverse_rel = $this->get_inverse_relationship($relationship)->get($reverse_key);
        echo "REGISTRY::" . __FUNCTION__ . " => relationships:\n";
        var_dump($relationship);
        var_dump($reverse_rel);
        echo "REGISTRY::" . __FUNCTION__ . " => should call printf then get_func\n";
        // throw new \Exception('I failed here');
        $this_rel_type = $relationship->get_f('rel_type');
        $reverse_rel_type = $reverse_rel->get('rel_type');
        printf("\n--- SHOULD CALL BLOODY GET FUNC with: %s, %s, %s\n", $method, $this_rel_type, $reverse_rel_type);
        $a =  $this->get_func(
            $method,
            $this_rel_type,
            $reverse_rel_type
            
        );
        var_dump($a);
        return $a;
    }

    /**
     * Get relation type from object - related object relation types
     */
    public function get_relation_type($this_rel_type, $reverse_rel_type) {
        if(
            ($this_rel_type === 'has_one' && $reverse_rel_type === 'belongs_to') ||
            ($reverse_rel_type === 'has_one' && $this_rel_type === 'belongs_to')
        ) {
            return self::RELATION_ONE_TO_ONE;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'belongs_to') {
            return self::RELATION_ONE_TO_MANY;
        }
        else if($this_rel_type === 'belongs_to' && $reverse_rel_type === 'has_many') {
            return self::RELATION_MANY_TO_ONE;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'has_many') {
            return self::RELATION_MANY_TO_MANY;
        }
        else throw new \Exception("NOT IMPLEMENTED for $this_rel_type, $reverse_rel_type\n");
    }

    /**
     * Get relation type from object - related object relation types
     */
    public function get_create_or_update_func_args($relationship, $reverse_rel) {
        $this_rel_type    = $relationship->get_f('rel_type');
        $reverse_rel_type = $reverse_rel->get_f('rel_type');
        if ($this_rel_type === 'has_one' && $reverse_rel_type === 'belongs_to') {
            return $this_rel_type->get_f('type_s') . '_' . $reverse_rel_type->get_f('type_s');
        }
        else if($reverse_rel_type === 'has_one' && $this_rel_type === 'belongs_to') {
        
            
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'belongs_to') {
            return 'get_related_one_to_many';
        }
        else if($this_rel_type === 'belongs_to' && $reverse_rel_type === 'has_many') {
            return self::RELATION_MANY_TO_ONE;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'has_many') {
            return self::RELATION_MANY_TO_MANY;
        }
        else throw new \Exception("NOT IMPLEMENTED for $this_rel_type, $reverse_rel_type\n");
    }

    /**
     * Get relation type from object - related object relation types
     */
    public function get_func($method, $this_rel_type, $reverse_rel_type) {
        echo "get_func #1\n";
        var_dump(func_get_args());
        $prefix = $method === 'GET' ? 'get' : 'set';
        echo "get_func #2 prefix\n";
        if ($this_rel_type === 'has_one' && $reverse_rel_type === 'belongs_to') {
            echo "#### 1to1\n";
            return $prefix . '_one_to_one_relatee';
        }
        else if($reverse_rel_type === 'has_one' && $this_rel_type === 'belongs_to') {
            echo "#### 1belongs\n";
            return $prefix . '_one_to_one_owner';
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'belongs_to') {
            echo "#### one2many\n";
            return $prefix . '_one_to_many';
        }
        else if($this_rel_type === 'belongs_to' && $reverse_rel_type === 'has_many') {
            echo "#### many2one\n";
            return self::RELATION_MANY_TO_ONE;
        }
        else if($this_rel_type === 'has_many' && $reverse_rel_type === 'has_many') {
            echo "#### many2many\n";
            return self::RELATION_MANY_TO_MANY;
        }
        else {
            echo "#### fucking ERROR\n";
            throw new \Exception("NOT IMPLEMENTED for $this_rel_type, $reverse_rel_type\n");
        }
    }


    /**
     * e.g.
     * Get passport for person
     * Needs: 
     *   - rel_type (e.g. person_passport)
     *   - what do you want to get? Passport id from Person? Or Person id from Passport?
     *
     * $rel_type = person_passport (person owns passport, hence person comes first in $rel_type)
     * $where_relatee_field = 'object2_id' (we want the passport id related to person)
     * $where_owner_field   = 'object1_id' (we want the passport id related to person)
     */
    function get_related_one_to_one_relatee($rel_type, $owner_id) {
        $res = $wpdb->get_results(
            "SELECT object2_id FROM {$this->pivot_table} WHERE rel_type='$rel_type' AND 'object1_id' = $owner_id", ARRAY_A
        );
    }

    function get_related_one_to_one_owner($rel_type, $relatee_id) {
        $res = $wpdb->get_results(
            "SELECT object1_id FROM {$this->pivot_table} WHERE rel_type='$rel_type' AND 'object2_id' = $relatee_id", ARRAY_A
        );
    }

    // ATTENTION CAR IL FAUT ALORS QUE L'ENTREE de l'ancien relatee soit effacée !!!!!!
    // ICI c'est PAS OK si l'ancien relatee n'a plus d'owner!
    function set_related_one_to_one_relatee($rel_type, $owner_id, $relatee_id) {
        $res = $wpdb->get_results(
            "UPDATE {$this->pivot_table} SET object2_id = $relatee_id WHERE 'object1_id' = $owner_id", ARRAY_A
        );
    }

    // ATTENTION CAR IL FAUT ALORS QUE L'ENTREE de l'ancien owner soit effacée !!!!!!
    // PAR CONTRE, ICI c'est OK si l'ancien owner n'a plus de relatee!
    function set_related_one_to_one_owner($rel_type, $owner_id, $relatee_id) {
        $res = $wpdb->get_results(
            "UPDATE {$this->pivot_table} SET object1_id = $owner_id WHERE 'object2_id' = $relatee_id", ARRAY_A
        );
    }


    function get_related_one_to_one($rel_type, $owner_or_relatee_id, $table_field) {
    }


    function get_related_one_to_many($owner_type, $relatee_type, $owner_id) {
        $this_first = $this_rel_class > $rev_rel_class;
        $where_type = $this_first ? static::$type . '_' . $this_rel_class::$type : $this_rel_class::$type . '_' . static::$type;
        $where_id = $this_first ? 'object1_id' : 'object2_id';
        $relatee_id = $this_first ? 'object2_id' : 'object1_id';
        $res = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE rel_type='$where_type' AND $where_id = $object_id", ARRAY_A
        );

    }


//     protected function get_types() {
//         return $this->types['post'];
//     }

//     protected function get_type_keys() {
//         return array_keys($this->types['post']);
//     }

//     protected function get_taxonomies() {
//         return $this->types['taxonomy'];
//     }

//     protected function get_taxonomy_keys() {
//         return array_keys($this->types['taxonomy']);
//     }


//     protected function get_rest_route_class($singular_lc) {
//         return $this->rest_classes[$singular_lc];
//     }
    
}

?>