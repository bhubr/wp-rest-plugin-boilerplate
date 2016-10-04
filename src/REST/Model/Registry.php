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
            $model_descriptors[] = $this->add_model($class_name, $plugin_descriptor);
        }
        $model_descriptors->each(function($descriptor) {
            $relationships = $descriptor->get('relationships');
            $parsed_rels = $this->parse_model_relationships($relationships);
            // We simply replace the relationships collection
            $descriptor->put('relationships', $parsed_rels);
        });
        var_dump($this->registry);
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
            throw new \Exception("Cannot register duplicate model {$class_name::$singular} in registry");
        }

        $registry = $this->registry->put( $plural_lc, collect_f ( [
            'type'          => $class_name::$type,
            'singular_lc'   => $class_name::$singular,
            'namespace'     => $plugin_descriptor['rest_root'] . '/v' . $plugin_descriptor['rest_version'],
            'rest_type'     => $plugin_descriptor['rest_type'],
            'class'         => $class_name,
            // 'relationships' => $this->parse_model_relationships(
            //     collect_f($class_name::$relations)
            // )
            'relationships' => collect_f($class_name::$relations)
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

    public function parse_model_relationships($relationships) {
        return $relationships->map([$this, 'parse_relationship']);
    }

    public function parse_relationship($relationship_descriptor, $relationship_attr) {
        $desc_bits = explode(':', $relationship_descriptor);
        $rel_class = $desc_bits[0];
        $rel_type = $desc_bits[1];
        $output = [
            'type'     => $rel_class::$plural,
            'plural'   => array_search($rel_type, ['has_one', 'belongs_to']) === false,
            'rel_type' => $desc_bits[1],
        ];
        if( count( $desc_bits ) > 2 ) $output['inverse'] = $desc_bits[2];
        return collect_f($output);
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