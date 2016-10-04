<?php

/**
 * Class Test_Model_Relationships
 *
 * @package Sandbox_Plugin
 */
use bhubr\REST\Payload\Formatter;
use bhubr\REST\Model\Registry;
use bhubr\REST\Model\Relationships;
use bhubr\Rest\Model\Post;
use bhubr\Rest\Model\Term;

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

        // $this->pl_1model_ok = rpb_build_plugin_descriptor('test-1model', MODELS_DIR, [
        //     'models_dir'       => 'model-registry/valid',
        //     'models_namespace' => 'registrytest\\valid\\',
        //     'rest_root'        => 'dummy',
        //     'rest_version'     => '3'
        // ]);

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
                'type'     => 'foo_cats',
                'plural'   => true,
                'rel_type' => 'has_many'
            ],
            'tags' => [
                'type'   => 'foo_tags',
                'plural' => true,
                'rel_type' => 'has_many'
            ]
        ];

        $foo_relationships = $this->mr->get_model('foos')->get_f('relationships');
        // $parsed_relationships = Relationships::parse_for_model(
        //     $foo_model_descriptor->get('relationships')
        // );

        $this->assertEquals(
            $expected_foo_relationships, $foo_relationships->toArray()
        );
    }

    public function test_check_relation_type() {
        $plugin_descriptor = rpb_build_plugin_descriptor('relationships', MODELS_DIR, [
            'models_dir'       => 'relationships',
            'models_namespace' => 'rel\\'
        ]);
        $this->mr->load_and_register_models($plugin_descriptor);

        // 0. chope la classe de l'objet
        $obj_class = 'rel\Person';
        $person_model_descriptor = $this->mr->get_model('persons');
        $person_model_relationships = $person_model_descriptor->get_f('relationships');


        $this->assertEquals(
            [
                // 'mybooks' => 'rel\Book:has_many',
                // 'mypass'  => 'rel\Passport:has_one:owner'
                'mybooks' => [
                    'type'     => 'books',
                    'plural'   => true,
                    'rel_type' => 'has_many',
                    'inverse'  => 'author'
                ],
                'mypass'  => [
                    'type'     => 'passports',
                    'plural'   => false,
                    'rel_type' => 'has_one',
                    'inverse'  => 'owner'
                ]
            ],
            $person_model_relationships->toArray()
        );
        // $person_mypass_rel = $person_model_relationships->get_f('mypass');
        // // 1. chope la clé de la relation
        // // $relation = 'mydumbass';
        // // 2. parse les relations
        // $mypass_relation_descriptor = Relationships::parse_relationship(
        //     $person_mypass_rel, 'mypass'
        // );
        // $this->assertEquals(
        //     [
        //         'type'     => 'passports',
        //         'plural'   => false,
        //         'rel_type' => 'has_one',
        //         'inverse'  => 'owner'
        //     ],
        //     $mypass_relation_descriptor->toArray()
        // );
        // // var_dump($relation_descriptor);
        // $related_type_plural = $mypass_relation_descriptor->get_f('type');
        // $related_model_descriptor = $this->mr->get_model($related_type_plural);
        // $related_model_relationships = $related_model_descriptor->get_f('relationships');
        // $related_passport_rel = $related_model_relationships->get_f(
        //     $mypass_relation_descriptor->get_f('inverse')
        // );
        // $passport_owner_relation_decriptor = Relationships::parse_relationship(
        //     $related_passport_rel, $mypass_relation_descriptor->get_f('inverse')
        // );
        // $this->assertEquals(
        //     [
        //         'type'     => 'persons',
        //         'plural'   => false,
        //         'rel_type' => 'belongs_to',
        //         'inverse'  => 'mypass'
        //     ],
        //     $passport_owner_relation_decriptor->toArray()
        // );

        $person = Post::_create('person', []);
        $passport = Post::_create('passport', []);

        $person = Post::_update('person', $person['id'], ['mypass' => $passport['id']]);
        $this->assertEquals([
            'id'         => 3,
            'slug'       => '3',
            'name'       => '',
            'mypass'     => 4
        ], $person);

        $passport = Post::_update('passport', $passport['id'], ['owner' => $person['id']]);
        $this->assertEquals([
            'id'           => 4,
            'slug'         => '4',
            'name'         => '',
            'owner'        => 3
        ], $passport);

        $passport_dup = rel\Person::fetch_relationship($person['id'], 'mypass');
        // var_dump($passport_owner_relation_decriptor);
        // 3. détermine la fonction pour lire/écrire la relation

    }
}