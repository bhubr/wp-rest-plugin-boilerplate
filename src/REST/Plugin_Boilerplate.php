<?php
namespace bhubr\REST;

require_once realpath(dirname(__FILE__) . '/../vendor/Inflect.php');
require_once realpath(dirname(__FILE__) . '/../../vendor/autoload.php');

define('WPRBP_LANG_DIR', realpath(__DIR__ . '/../../languages'));

class Plugin_Boilerplate {
    private static $_instance;
    protected $registered_plugins = [];
    protected $wp_plugins_dir;

    /**
     * Get unique class instance
     **/
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Plugin_Boilerplate();
        }
        return self::$_instance;
    }

    /**
     * Private constructor
     **/
    private function __construct()
    {
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
    public function register_plugin($plugin_name, $plugin_dir, $options = []) {
        $default_options = [
            'models_dir' => 'models',
            'models_namespace' => 'bhubr\\',
            'rest_type' => Payload\Formatter::JSONAPI,
            'rest_root' => 'bhubr',
            'rest_version' => 1
        ];
        $this->registered_plugins[$plugin_name] = array_merge([
            'plugin_name' => $plugin_name,
            'plugin_dir'  => $plugin_dir
        ], $default_options, $options);
    }


    /**
     * Register custom post types
     */
    public function register_types() {
        foreach($this->registered_plugins as $plugin_name => $plugin_descriptor) {
            Model\Registry::load_and_register_models($plugin_descriptor);
        }
    }


    /**
     * Load plugin textdomain
     */
    public function load_textdomains() {
        $locale = get_locale();
        $wprpb_mo_file = WPRBP_LANG_DIR . "/wprpb-$locale.mo";
        if (! load_textdomain( 'bhubr-wprbp', $wprpb_mo_file )) return false;
        
        foreach($this->registered_plugins as $plugin_name => $plugin_def) {
            $plugin_dir = $plugin_def['plugin_dir'];
            $mo_file = "$plugin_dir/languages/{$plugin_name}-$locale.mo";
            if (! load_textdomain( $plugin_name, $mo_file )) return false;
        }
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
