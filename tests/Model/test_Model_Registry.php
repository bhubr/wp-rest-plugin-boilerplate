<?php

/**
 * Class Test_Model_Registry
 *
 * @package Sandbox_Plugin
 */

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

/**
 * Sample test case.
 */
class Test_Model_Registry extends WP_UnitTestCase {

    /**
     * Model Registry instance
     */
    protected $mr;


    function setUp() {
        $model_registry = bhubr\REST\Model\Registry::get_instance();
        $this->mr = new SebastianBergmann\PeekAndPoke\Proxy($model_registry);

        $this->pl_1model_ok = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'model-registry/valid',
            'models_namespace' => 'registrytest\\valid\\'
        ]);
    }

    /**
     * Unregister registered WP post types after each test
     */
    function tearDown() {
        unregister_post_type('valid_type');
    }

    function test_get_model_files() {
        $model_files = $this->mr->get_model_files($this->pl_1model_ok);
        $this->assertEquals([
            MODELS_DIR . '/model-registry/valid/Type.php',
        ], $model_files);
    }

    function test_load_model_file_ok() {
        $this->assertFalse(class_exists('registrytest\valid\Type'));
        $this->mr->load_model_file(
            MODELS_DIR . '/model-registry/valid/Type.php',
            $this->pl_1model_ok
        );
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
     * Error: class does not have missing properties
     * @expectedException Exception
     * @expectedExceptionMessage Missing required properties: [type, singular, plural, name_s, name_p, fields, relations] in registrytest\invalid\MissingProps
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
        $this->assertTrue(class_exists('registrytest\InvalidNS'));
    }


    // function test_registered_types() {
    //     $builtin_types = get_post_types(['_builtin' => true]);
    //     $all_types = array_merge(
    //         $builtin_types,
    //         [
    //           // 'dumbass'       => 'dumbass',
    //           // 'dumbmany'      => 'dumbmany',
    //           // 'dumbmany2many' => 'dumbmany2many',
    //           // 'dummy'         => 'dummy',
    //           'foo'           => 'foo',
    //         ]
    //     );
    //     $this->assertEquals( $all_types, get_post_types() );
    // }

    // function test_registered_taxonomies() {
    //     // Replace this with some actual testing code.
    //     $builtin_taxonomies = get_taxonomies(['_builtin' => true]);
    //     $all_taxos = array_merge(
    //         $builtin_taxonomies,
    //         [
    //             'foo_cat'      => 'foo_cat',
    //             'foo_tag'      => 'foo_tag',
    //             // 'dummyterm'    => 'dummyterm',
    //             // 'termany'      => 'termany',
    //             // 'termany2many' => 'termany2many',
    //             // 'termone'      => 'termone',
    //         ]
    //     );
    //     $this->assertEquals( $all_taxos, get_taxonomies() );
    // }

}
