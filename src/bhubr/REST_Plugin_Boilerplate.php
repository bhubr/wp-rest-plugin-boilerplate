<?php
namespace bhubr;
require_once 'vendor/Inflect.php';
require_once realpath(dirname(__FILE__) . '/../../vendor/autoload.php');

define('WPRBP_LANG_DIR', realpath(__DIR__ . '/../../languages'));

class REST_Plugin_Boilerplate {
    private static $_instance;
    protected $registered_plugins = [];
    protected $wp_plugins_dir;

    /**
     * Get unique class instance
     **/
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new REST_Plugin_Boilerplate();
        }
        return self::$_instance;
    }

    /**
     * Private constructor
     **/
    private function __construct()
    {
        // $wp_plugins_dir = realpath($plugin_dir . '/..');
        // $this->wp_plugins_dir = $wp_plugins_dir;
        add_action('plugins_loaded', array(&$this, 'load_textdomains'));
        add_action('init', array(&$this, 'register_types'));
        add_action('rest_api_init', function () {
            $controller = new REST_Controller();
            $controller->register_routes();
        });
    }


    /**
     * Register a plugin
     */
    public function register_plugin($plugin_name, $plugin_dir) {
        // The old way... see register_types()
        // $this->registered_plugins[$plugin_name] = $plugin_def;
        $this->registered_plugins[$plugin_name] = $plugin_dir;
    }


    /**
     * Register custom post types
     */
    public function register_types() {
        // The old way. Keep it for now...
        //foreach($this->registered_plugins as $plugin_name => $plugin_def) {
        //    foreach($plugin_def['types'] as $name_slc => $type_def) {
        //        $name_s = __($type_def['name_s'], $plugin_name);
        //        Base_Model::register_type($name_slc, $name_s, $type_def);
        //        foreach($type_def['taxonomies'] as $tax_name_slc => $tax_def) {
        //            Base_Model::register_taxonomy($tax_name_slc, $tax_def['name_s'], $name_slc, $tax_def['fields']);
        //         }
        //     }
        //}

        $models = [
            'post' => [],
            'term' => []
        ];
        foreach($this->registered_plugins as $plugin_name => $plugin_dir) {
            $models_dir = "$plugin_dir/models";
            if (! file_exists($models_dir)) {
                throw new \Exception("Error for plugin $plugin_name: models dir $models_dir doesn't exist");
            }
            $model_files = glob("$models_dir/*.php");

            foreach($model_files as $file) {
                require_once $file;
                $class_name = 'bhubr\\' . basename($file, '.php');
                $name_slc = $class_name::$singular;
                $models[$class_name::$type][] = $class_name;
                // $models[$class_name::$type][$name_slc] = [
                //     'name_s' => $class_name::$name_s,
                //     'fields' => $class_name::$fields
                // ];
                // if ($class_name::$type === 'term') {
                //     $models[$class_name::$type][$name_slc]['post_type'] = ::$post_type;
                // }
            }
        }
        foreach ($models['post'] as $class_name) {
            Base_Model::register_type($class_name);
        }
        foreach ($models['term'] as $class_name) {
            Base_Model::register_taxonomy($class_name);
        }
    }


    /**
     * Load plugin textdomain
     */
    public function load_textdomains() {
        $locale = get_locale();
        foreach($this->registered_plugins as $plugin_name => $plugin_def) {
            $mo_file = $this->wp_plugins_dir . "/$plugin_name/languages/{$plugin_name}-$locale.mo";
            if (! load_textdomain( $plugin_name, $mo_file )) return false;
        }
        $wprpb_mo_file = WPRBP_LANG_DIR . "/wprpb-$locale.mo";
        if (! load_textdomain( 'bhubr-wprbp', $wprpb_mo_file )) return false;
        return true;
    }


    /**
     * Create association with meta table
     * @global type $wpdb
     */
    function create_assoc_with_meta_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rpb_many_to_many';
        // Return if table exists
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            return;
        }
        if (!empty ($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        if (!empty ($wpdb->collate))
            $charset_collate .= " COLLATE {$wpdb->collate}";
            // Prepare sql
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                object1_id bigint(20) NOT NULL,
                object2_id bigint(20) NOT NULL,
                rel_type ENUM('post_post', 'post_term', 'term_post', 'term_term'),
                meta_value longtext DEFAULT NULL,

                UNIQUE KEY id (id)
            ) {$charset_collate};";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
    }

    /**
     * Create meta table on plugin activation
     * @global type $wpdb
     */
    // function create_term_meta_tables($plugin_name) {
    //     global $wpdb;
    //     $types = $this->registered_plugins[$plugin_name]['types'];
    //     foreach($types as $type_lc => $type_def) {
    //         if (! array_key_exists('taxonomies', $type_def)) continue;
    //         $taxonomies = array_keys($type_def['taxonomies']);

    //         // Exit if type has no associated taxonomy
    //         foreach($taxonomies as $taxonomy) {
    //             // if( !$has_meta ) continue;
    //             $tax_meta_name = $taxonomy . 'meta';
    //             $table_name = $wpdb->prefix . $tax_meta_name;

    //             // Return if table exists
    //             if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    //                 return;
    //             }
    //             if (!empty ($wpdb->charset))
    //                 $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    //             if (!empty ($wpdb->collate))
    //                 $charset_collate .= " COLLATE {$wpdb->collate}";
    //             // Prepare sql
    //             $sql = "CREATE TABLE $table_name (
    //                 meta_id bigint(20) NOT NULL AUTO_INCREMENT,
    //                 {$taxonomy}_id bigint(20) NOT NULL default 0,

    //                 meta_key varchar(255) DEFAULT NULL,
    //                 meta_value longtext DEFAULT NULL,

    //                 UNIQUE KEY meta_id (meta_id)
    //             ) {$charset_collate};";

    //             require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    //             dbDelta($sql);
    //         }
    //     }
    // }

    /**
     * Delete meta table on plugin delete
     * @global type $wpdb
     */
    // function delete_term_meta_tables($plugin_name) {
    //     global $wpdb;
    //     $types = $this->registered_plugins[$plugin_name]['types'];
    //     foreach($types as $type_lc => $type_def) {
    //         if (! array_key_exists('taxonomies', $type_def)) continue;
    //         $taxonomies = array_keys($type_def['taxonomies']);

    //         // Exit if type has no associated taxonomy
    //         foreach($taxonomies as $taxonomy) {
    //             // if( !$has_meta ) continue;
    //             $tax_meta_name = $taxonomy . 'meta';
    //             $table_name = $wpdb->prefix . $tax_meta_name;

    //             // Return if table exists
    //             if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    //                 return;
    //             }
    //             $sql = "DROP TABLE $table_name";

    //             require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    //             dbDelta($sql);
    //         }
    //     }
    // }
}
