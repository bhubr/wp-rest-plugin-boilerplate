<?php
namespace bhubr\REST\Model;

use Underscore\Underscore as __;

abstract class Model_Registry {

    protected static $registry = [];
    protected static $type_class_map = [
        'post' => 'bhubr\Post_Model',
        'term' => 'bhubr\Term_Model'
    ];

    /**
     * Scan the model folder to retrieve model file names
     */
    public static function get_model_files($plugin_descriptor) {
        $models_dir  = $plugin_descriptor['models_dir'];
        $plugin_name = $plugin_descriptor['plugin_name'];
        if (! file_exists($models_dir)) {
            throw new \Exception("Error for plugin $plugin_name: models dir $models_dir doesn't exist");
        }
        return glob("$models_dir/*.php");
    }

    /**
     * Load an individual model class
     */
    public static function load_model_file($file, $plugin_descriptor) {
        $required_properties = ['type', 'singular', 'plural', 'name_s', 'name_p', 'fields', 'relations'];
        require_once $file;
        $class_name = 'bhubr\\' . basename($file, '.php');
        foreach($required_properties as $prop) {
            if(! property_exists($class_name, $prop)) {
                throw new \Exception("Missing property $prop in $class_name");
            }
        }
        self::register_model($class_name, $plugin_descriptor);
    }

    /**
     * Load then register models
     */
    public static function load_and_register_models($plugin_descriptor) {
        $model_files = self::get_model_files($plugin_descriptor);
        foreach($model_files as $file) {
            self::load_model_file($file, $plugin_descriptor);
        }
        self::register_models_to_wordpress();
    }

    /**
     * Prepare data for use in REST controller
     */
    public static function register_model($class_name, $plugin_descriptor) {
        $plural_lc = $class_name::$plural;
        if (array_key_exists($plural_lc, self::$registry)) {
            throw new \Exception("Duplicate model {$class_name::$singular} in registry");
        }
        self::$registry[$plural_lc] = [
            'type'         => $class_name::$type,
            'singular_lc'  => $class_name::$singular,
            'namespace'    => $plugin_descriptor['rest_root'] . '/v' . $plugin_descriptor['rest_ver'],
            'rest_type'    => $plugin_descriptor['rest_type'],
            'class'        => $class_name
        ];
    }

    /**
     * public static function get

    /**
     * Fetch data for rest controller
     */
    public static function get_model_data_for_rest($plural_lc) {
        return self::$registry[$plural_lc];
    }

    public static function get_rest_route_class($plural_lc) {
        return self::get_model_data_for_rest($plural_lc)['class'];
    }


    /**
     * Get rest keys
     */
    public static function get_rest_bases() {
        return array_keys(self::$registry);
    }


    /**
     * Register loaded models
     */
    public static function register_models_to_wordpress() {
        $registry_values = collect(array_values(self::$registry));
        $types = $registry_values->groupBy('type')->toArray();
        foreach ($types['post'] as $descriptor) {
            Post_Model::register_model_key($descriptor['singular_lc']);
            self::register_wp_post_type($descriptor['class']);
        }
        foreach ($types['term'] as $class_name) {
            Term_Model::register_model_key($descriptor['singular_lc']);
            self::register_wp_post_type($descriptor['class']);
        }

    }

    /**
     * Register a post model/type
     */
    public static function register_wp_post_type($class_name) {
        // die("$class_name\n");
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
                'add_new'            => __("Add", "bhubr-wppc"),
                'add_new_item'       => sprintf(__("Add %s", "bhubr-wppc"), $name_s),
                'edit_item'          => sprintf(__("Edit %s", "bhubr-wppc"), $name_s),
                'new_item'           => sprintf(__("New %s", "bhubr-wppc"), $name_s),
                'all_items'          => sprintf(__("All %s", "bhubr-wppc"), $name_s),
                'view_item'          => sprintf(__("View %s", "bhubr-wppc"), $name_s),
                'search_items'       => sprintf(__("Search %s", "bhubr-wppc"), $name_p),
                'not_found'          => __("Not found", "bhubr-wppc"),
                'not_found_in_trash' => __("No item found in Trash", "bhubr-wppc"),
                // 'menu_name'          => "$name_s Items", "wp_{$singular_lc}_items"
            ],
            'description'   => "$name_s Items",
            'public'        => true,
            // 'menu_position' => self::$menu_pos++,
            'supports'      => ['title', 'editor', 'thumbnail'],
            'exclude_from_search' => true
        ];

        // self::$types['post'][$singular_lc] = $fields; 
        // self::$rest_bases[] = $plural_lc;
        // self::$rest_classes[$plural_lc] = $class_name;

        // Allow wrapping native WP classes with objects... not sure it's a good idea...
        $wp_types_kv = get_post_types(['_builtin' => true]);
        $wp_types = array_keys($wp_types_kv);
        if (array_search($singular_lc, $wp_types) !== false) return;

        $res = register_post_type($singular_lc, $args);
        // var_dump($res);
        var_dump(get_post_types());
    }

    public static function register_wp_taxonomy($class_name) {
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
                'add_new_item' => sprintf(__("Add %s", "bhubr-wppc"), $name_s),
            ],
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        ];

        // self::$types['taxonomy'][$singular_lc] = $fields; 
        // self::$rest_bases[] = $plural_lc;
        // self::$rest_classes[$plural_lc] = $class_name;

        register_taxonomy( $singular_lc, $type_lc, $args );
    }

//     public static function get_types() {
//         return self::$types['post'];
//     }

//     public static function get_type_keys() {
//         return array_keys(self::$types['post']);
//     }

//     public static function get_taxonomies() {
//         return self::$types['taxonomy'];
//     }

//     public static function get_taxonomy_keys() {
//         return array_keys(self::$types['taxonomy']);
//     }


//     public static function get_rest_route_class($singular_lc) {
//         return self::$rest_classes[$singular_lc];
//     }
    
}

?>