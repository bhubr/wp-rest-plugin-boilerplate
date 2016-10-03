<?php

/**
 * Class Test_Model_Relationships
 *
 * @package Sandbox_Plugin
 */
use bhubr\REST\Payload\Formatter;
use bhubr\REST\Model\Registry;
use bhubr\REST\Model\Relationships;

/**
 * Sample test case.
 */
class Test_Model_Relationships extends WP_UnitTestCase {

    /**
     * Model Registry instance
     */
    protected $mr;


    function setUp() {
        // $model_registry = 
        $this->mr = Registry::get_instance();
        //new SebastianBergmann\PeekAndPoke\Proxy($model_registry);

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
    }

    public function test_parse_for_model() {
        $plugin_descriptor = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
            'models_dir'       => 'foo',
            'models_namespace' => 'foo\\'
        ]);
        $this->mr->load_and_register_models($plugin_descriptor);

        $expected_foo_relationships = [
            'categories' => [
                'type'   => 'foo_cats',
                'plural' => true
            ],
            'tags' => [
                'type'   => 'foo_tags',
                'plural' => true
            ]
        ];

        $foo_model_descriptor = $this->mr->get_model('foos');
        $parsed_relationships = Relationships::parse_for_model(
            $foo_model_descriptor->get('relationships')
        );

        $this->assertEquals(
            $expected_foo_relationships, $parsed_relationships->toArray()
        );
    }
}