<?php
namespace bhubr\REST\Model;

class Registry {

    /**
     * Holds information on each model
     */
    protected $registry = [];

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
    protected function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Registry();
        }
        return self::$_instance;
    }


    /**
     * Load then register models
     */
    public function load_and_register_models($plugin_descriptor) {
        $model_files = $this->get_model_files($plugin_descriptor);
        foreach($model_files as $file) {
            $this->load_model_file($file, $plugin_descriptor);
        }
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
        $required_properties = ['type', 'singular', 'plural', 'name_s', 'name_p', 'fields', 'relations'];
        require_once $file;
        $class_name = $plugin_descriptor['models_namespace'] . basename($file, '.php');
        if (! class_exists($class_name)) {
            throw new \Exception("Could not find class class_name in $file. Check namespacing: " . $plugin_descriptor['models_namespace']);
        }
        foreach($required_properties as $prop) {
            if(! property_exists($class_name, $prop)) {
                throw new \Exception("Missing property $prop in $class_name");
            }
        }
        $this->register_model($class_name, $plugin_descriptor);
    }


    /**
     * Prepare data for use in REST controller
     */
    protected function register_model($class_name, $plugin_descriptor) {
        $plural_lc = $class_name::$plural;
        if (array_key_exists($plural_lc, $this->registry)) {
            throw new \Exception("Duplicate model {$class_name::$singular} in registry");
        }
        $this->registry[$plural_lc] = [
            'type'         => $class_name::$type,
            'singular_lc'  => $class_name::$singular,
            'namespace'    => $plugin_descriptor['rest_root'] . '/v' . $plugin_descriptor['rest_version'],
            'rest_type'    => $plugin_descriptor['rest_type'],
            'class'        => $class_name
        ];
    }


    /**
     * Fetch data for rest controller
     */
    public function get_model_data_for_rest($plural_lc) {
        return $this->registry[$plural_lc];
    }


    /**
     * Get class for given route's plural/lowercase model name
     */
    public function get_rest_route_class($plural_lc) {
        return $this->get_model_data_for_rest($plural_lc)['class'];
    }


    /**
     * Get rest keys
     */
    public function get_rest_bases() {
        return array_keys($this->registry);
    }


    /**
     * Register loaded models
     */
    protected function register_models_to_wordpress() {
        $registry_values = collect(array_values($this->registry));
        $types = $registry_values->groupBy('type')->toArray();
        if (array_key_exists('post', $types)) {
            foreach ($types['post'] as $descriptor) {
                Post::register_model_key($descriptor['singular_lc']);
                $this->register_wp_post_type($descriptor['class']);
            }
        }
        if (array_key_exists('term', $types)) {
            foreach ($types['term'] as $class_name) {
                Term::register_model_key($descriptor['singular_lc']);
                $this->register_wp_post_type($descriptor['class']);
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
                'add_new_item' => sprintf(__("Add %s", "bhubr-wprbp"), $name_s),
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