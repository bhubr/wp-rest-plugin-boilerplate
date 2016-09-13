<?php
namespace bhubr;
require_once 'vendor/Inflect.php';

define('WPRBP_LANG_DIR', realpath(__DIR__ . '/../../languages'));

class REST_Plugin_Boilerplate {
    private static $_instance;
    protected $registered_plugins = [];
    protected $wp_plugins_dir;

    /**
     * Get unique class instance
     **/
    public static function get_instance($wp_plugins_dir)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new REST_Plugin_Boilerplate($wp_plugins_dir);
        }
        return self::$_instance;
    }

    /**
     * Private constructor
     **/
    private function __construct($plugin_dir)
    {
        $wp_plugins_dir = realpath($plugin_dir . '/..');
        $this->wp_plugins_dir = $wp_plugins_dir;
        add_action('plugins_loaded', array(&$this, 'load_textdomains'));
        add_action('init', array(&$this, 'register_types'));
        add_action('rest_api_init', function () {
            $types = Base_Model::get_types();
            foreach($types as $type_lc => $type_definition) {
                $type_plural = \Inflect::pluralize($type_lc);
                $controller = new \REST_Controller();
                $controller->register_routes($type_plural);

                // register_rest_route( 'myplugin/v1', "/$type_plural/(?P<id>\d+)", [
                //     'methods' => 'GET',
                //     'callback' => function($data) use($type_lc) {
                //         $post_id = $data['id'];
                //         return Post_Model::read($type_lc, $post_id);
                //     },
                // ], [
                //     'methods' => 'DELETE',
                //     'callback' => function($data) use($type_lc) {
                //         $post_id = $data['id'];
                //         return Post_Model::delete($type_lc, $post_id);
                //     },
                // ] );
                // register_rest_route( 'myplugin/v1', "/$type_plural", array(
                //     'methods' => 'POST',
                //     'callback' => function(\WP_REST_Request $request) use($type_lc) {
                //         $data = $request->get_json_params();
                //         return Post_Model::create($type_lc, $data);
                //     },
                // ) );
            }
        });
    }


    /**
     * Register a plugin
     */
    public function register_plugin($plugin_name, $plugin_def) {
        $this->registered_plugins[$plugin_name] = $plugin_def;
    }


    /**
     * Register custom post types
     */
    public function register_types() {
        foreach($this->registered_plugins as $plugin_name => $plugin_def) {
            foreach($plugin_def['types'] as $name_slc => $type_def) {
                $name_s = __($type_def['name_s'], $plugin_name);
                Base_Model::register_type($name_slc, $name_s, $type_def);
                foreach($type_def['taxonomies'] as $tax_name_slc => $tax_def) {
                    Base_Model::register_taxonomy($tax_name_slc, $tax_def['name_s'], $name_slc, $tax_def['fields']);
                }
            }
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
     * Create meta table form pricing categories on plugin activation
     * @global type $wpdb
     */
    function create_term_meta_tables($plugin_name) {
        global $wpdb;
        $types = $this->registered_plugins[$plugin_name]['types'];
        foreach($types as $type_lc => $type_def) {
            if (! array_key_exists('taxonomies', $type_def)) continue;
            $taxonomies = array_keys($type_def['taxonomies']);

            // Exit if type has no associated taxonomy
            foreach($taxonomies as $taxonomy) {
                // if( !$has_meta ) continue;
                $tax_meta_name = $taxonomy . 'meta';
                $table_name = $wpdb->prefix . $tax_meta_name;

                // Return if table exists
                if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                    return;
                }
                if (!empty ($wpdb->charset))
                    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
                if (!empty ($wpdb->collate))
                    $charset_collate .= " COLLATE {$wpdb->collate}";
                // Prepare sql
                $sql = "CREATE TABLE {$table_name} (
                    meta_id bigint(20) NOT NULL AUTO_INCREMENT,
                    {$taxonomy}_id bigint(20) NOT NULL default 0,

                    meta_key varchar(255) DEFAULT NULL,
                    meta_value longtext DEFAULT NULL,

                    UNIQUE KEY meta_id (meta_id)
                ) {$charset_collate};";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }
        }
    }
}
