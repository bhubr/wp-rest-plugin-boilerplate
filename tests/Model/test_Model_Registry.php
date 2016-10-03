<?php

/**
 * Class Test_Model_Registry
 *
 * @package Sandbox_Plugin
 */
use bhubr\REST\Payload\Formatter;
use bhubr\REST\Model\Registry;

if ( ! function_exists( 'unregister_post_type' ) ) :
function unregister_post_type( $post_type ) {
    global $wp_post_types;
    if ( isset( $wp_post_types[ $post_type ] ) ) {
        unset( $wp_post_types[ $post_type ] );
        return true;
    }
    return false;
}
endif;

if ( ! function_exists( 'unregister_taxonomy' ) ) :
function unregister_taxonomy( $taxonomy ) {
    global $wp_taxonomies;
    if ( isset( $wp_taxonomies[ $taxonomy ] ) ) {
        unset( $wp_taxonomies[ $taxonomy ] );
        return true;
    }
    return false;
}
endif;

/**
 * Sample test case.
 */
class Test_Model_Registry extends WP_UnitTestCase {

    /**
     * Model Registry instance
     */
    protected $mr;


    function setUp() {
        $model_registry = Registry::get_instance();
        $this->mr = new SebastianBergmann\PeekAndPoke\Proxy($model_registry);

        $this->pl_1model_ok = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'model-registry/valid',
            'models_namespace' => 'registrytest\\valid\\',
            'rest_root'        => 'dummy',
            'rest_version'     => '3'
        ]);
    }

    /**
     * Unregister registered WP post types after each test
     */
    function tearDown() {
        $obj         = Registry::get_instance();
        $refObject   = new ReflectionObject( $obj );
        $refProperty = $refObject->getProperty( '_instance' );
        $refProperty->setAccessible( true );
        $refProperty->setValue(null);
        unregister_post_type('valid_type');
        unregister_taxonomy('valid_cat');
    }

    function test_get_model_files() {
        $model_files = $this->mr->get_model_files($this->pl_1model_ok);
        $this->assertEquals([
            MODELS_DIR . '/model-registry/valid/Cat.php',
            MODELS_DIR . '/model-registry/valid/Type.php',
        ], $model_files);
    }

    function test_load_model_file_ok() {
        $this->assertFalse(class_exists('registrytest\valid\Type'));
        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
        $this->assertEquals('registrytest\valid\Type', $class_name);
        $this->assertTrue(class_exists('registrytest\valid\Type'));
    }


    /**
     * Error: class not found (probably because of namespace mismatch)
     * @expectedException Exception
     * @expectedExceptionMessage Class expectednamespace\InvalidNS not found in model-registry/invalid/InvalidNS.php. Check namespacing in file => expectednamespace\
     */
    public function test_load_model_file_nok_namespace() {
        $this->assertFalse(class_exists('expectednamespace\InvalidNS'));
        $plugin_descriptor = rpb_build_plugin_descriptor('test-1model-invalid', MODELS_DIR, [
            'models_dir'       => 'model-registry/invalid',
            'models_namespace' => 'expectednamespace\\'
        ]);
        $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/invalid/InvalidNS.php',
            $plugin_descriptor
        );
    }

    /**
     * Error: class does not have required properties
     * @expectedException Exception
     * @expectedExceptionMessage Missing required properties: [singular, plural, name_s, name_p, fields, relations] in registrytest\invalid\MissingProps
     */
    public function test_load_model_file_nok_props() {
        $this->assertFalse(class_exists('registrytest\invalid\MissingProps'));
        $plugin_descriptor = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'model-registry/invalid',
            'models_namespace' => 'registrytest\\invalid\\'
        ]);
        $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/invalid/MissingProps.php',
            $plugin_descriptor
        );
    }


    /**
     * Error: Term class misses required_property post_type
     * @expectedException Exception
     * @expectedExceptionMessage Missing required properties: [post_type] in registrytest\invalid\MissingPropsCat
     */
    public function test_load_model_file_nok_props_term_model() {
        $this->assertFalse(class_exists('registrytest\invalid\MissingPropsCat'));
        $plugin_descriptor = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'model-registry/invalid',
            'models_namespace' => 'registrytest\\invalid\\'
        ]);
        $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/invalid/MissingPropsCat.php',
            $plugin_descriptor
        );
    }


    /**
     * Get model keys
     */
    public function test_get_models_keys() {
        $this->assertEquals([], $this->mr->get_models_keys());
    }


    /**
     * Get non-existent model key
     * @expectedException Exception
     * @expectedExceptionMessage Model plural/lowercase key 'notfound' not found in registry
     */
    public function test_get_model_invalid_key() {
        $m = $this->mr->get_model('notfound');
    }


    /**
     * Add a model
     */
    public function test_add_get_model_ok() {
        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
        $this->mr->add_model($class_name, $this->pl_1model_ok);
        $this->assertEquals(['valid_types'], $this->mr->get_models_keys());
        $this->assertEquals([
            'type'         => 'post',
            'singular_lc'  => 'valid_type',
            'namespace'    => 'dummy/v3',
            'rest_type'    => Formatter::SIMPLE,
            'class'        => 'registrytest\valid\Type'

        ], $this->mr->get_model('valid_types')->toArray());
        $this->assertEquals('registrytest\valid\Type', $this->mr->get_model_class('valid_types'));
    }

    /**
     * Error: class does not have missing properties
     * @expectedException Exception
     * @expectedExceptionMessage Cannot register duplicate model valid_type in registry
     */
    public function test_add_model_nok_duplicate() {
        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
        $this->mr->add_model($class_name, $this->pl_1model_ok);
        $this->mr->add_model($class_name, $this->pl_1model_ok);
    }

    /**
     * Register a WP custom post type
     */
    function test_register_wp_post_type() {
        $this->assertEquals([], $this->mr->get_models_keys());
        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
        $this->mr->add_model($class_name, $this->pl_1model_ok);
        $this->mr->register_wp_post_type('registrytest\valid\Type');

        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge($builtin_types, ['valid_type' => 'valid_type']);
        $this->assertEquals( $all_types, get_post_types() );

        $type_objects = get_post_types(['_builtin' => false], 'objects');
        $type_labels = $type_objects['valid_type']->labels;
        $this->assertEquals($type_labels->name, "Valid Types");
        $this->assertEquals($type_labels->singular_name, "Valid Type");
        $this->assertEquals($type_labels->add_new, "Add");
        $this->assertEquals($type_labels->add_new_item, "Add Valid Type");
        $this->assertEquals($type_labels->new_item, "New Valid Type");
        $this->assertEquals($type_labels->view_item, "View Valid Type");
        $this->assertEquals($type_labels->search_items, "Search Valid Types");
        $this->assertEquals($type_labels->not_found, "Not found");
        $this->assertEquals($type_labels->not_found_in_trash, "No item found in Trash");
    }

    /**
     * Register a WP custom post type
     */
    function test_register_wp_taxonomy() {
        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
        $this->mr->add_model($class_name, $this->pl_1model_ok);
        $this->mr->register_wp_post_type('registrytest\valid\Type');

        $class_name = $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Cat.php',
            $this->pl_1model_ok
        );
        $this->mr->add_model($class_name, $this->pl_1model_ok);
        $this->mr->register_wp_taxonomy('registrytest\valid\Cat');

        $builtin_taxos = get_taxonomies(['_builtin' => true]);
        $all_taxos = array_merge($builtin_taxos, ['valid_cat' => 'valid_cat']);
        $this->assertEquals( $all_taxos, get_taxonomies() );

        $taxo_objects = get_taxonomies(['_builtin' => false], 'objects');
        $type_labels = $taxo_objects['valid_cat']->labels;
        $this->assertEquals($type_labels->name, "Valid Cats");
        $this->assertEquals($type_labels->singular_name, "Valid Cat");
        $this->assertEquals($type_labels->add_new_item, "Add Valid Cat");
        $this->assertEquals($type_labels->new_item_name, "New Valid Cat");
        $this->assertEquals($type_labels->view_item, "View Valid Cat");
        $this->assertEquals($type_labels->search_items, "Search Valid Cats");
        $this->assertEquals($type_labels->not_found, "Not found");
    }

    /**
     * High-level register
     */
    public function test_register_models_to_wordpress() {
        $plugin_descriptor = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'foo',
            'models_namespace' => 'foo\\'
        ]);
        $this->mr->load_and_register_models($plugin_descriptor);

        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge($builtin_types, ['foo' => 'foo']);
        $this->assertEquals( $all_types, get_post_types() );

        $builtin_taxos = get_taxonomies(['_builtin' => true]);
        $all_taxos = array_merge($builtin_taxos, [
            'foo_cat' => 'foo_cat',
            'foo_tag' => 'foo_tag'
        ]);
        $this->assertEquals( $all_taxos, get_taxonomies() );
    }

}
