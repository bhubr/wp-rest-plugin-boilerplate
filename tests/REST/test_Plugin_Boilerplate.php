<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\REST\Payload\Formatter;

/**
 * Sample test case.
 */
class Test_Pouet extends WP_UnitTestCase {
    private $rbp;

    /**
     * Instantiate plugin base, and register a dummy plugin with one custom post type
     * and two custom taxonomies for this type
     */
    function setUp() {
        $this->rbp = bhubr\REST\Plugin_Boilerplate::get_instance();
        $this->rbp->register_plugin('dummy-plugin', RESOURCES_DIR, [
            'models_dir' => 'models/foo', 'models_namespace' => 'foo\\'
        ]);
    }


    /**
     * Ensure that text domain is properly loaded with changed locale (fr_FR)
     */
    function test_load_textdomains_french() {
        global $locale;
        $locale = 'fr_FR';

        $success = $this->rbp->load_textdomains();
        $this->assertEquals('fr_FR', $locale);
        $this->assertEquals('fr_FR', get_locale());
        $this->assertTrue($success);
    }

    /**
     * Check proper translation for boilerplate textdomain
     */
    function test_translation_plugin_boilerplate() {
        $this->assertEquals('Base de Plugin WordPress par T1z', __('WPPC_PLUGIN_NAME', 'bhubr-wprbp'));
    }

    /**
     * Check proper translation for text plugin textdomain
     */
    function test_translation_test_plugin() {
        $this->assertEquals('Bienvenue, Dummy', __('WELCOME_DUMMY', 'dummy-plugin'));
    }



    /**
     * Ensure that plugin custom post types are registered, with correct labels
     */
    function test_register_post_types() {
        $this->rbp->register_types();

        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge($builtin_types, ['foo' => 'foo']);
        $this->assertEquals( $all_types, get_post_types() );

        $type_objects = get_post_types(['_builtin' => false], 'objects');
        $type_labels = $type_objects['foo']->labels;
        $this->assertEquals($type_labels->name, "Foos");
        $this->assertEquals($type_labels->singular_name, "Foo");
        $this->assertEquals($type_labels->add_new, "Ajouter");
        $this->assertEquals($type_labels->add_new_item, "Ajouter Foo");
        $this->assertEquals($type_labels->new_item, "Nouveau Foo");
        $this->assertEquals($type_labels->view_item, "Voir Foo");
        $this->assertEquals($type_labels->search_items, "Rechercher Foos");
        $this->assertEquals($type_labels->not_found, "Non trouvé");
        $this->assertEquals($type_labels->not_found_in_trash, "Aucun élément dans la Corbeille");
    }

}
