<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

/**
 * Sample test case.
 */
class CommonsTest extends WP_UnitTestCase {

    private $rbp;

    function setUp() {
        $this->rbp = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
    }

    /**
     * Ensure that text domain is properly loaded with changed locale (fr_FR)
     */
    function test_load_textdomains_french() {
        global $locale;
        // $this->rbp->register_plugin('rbp-test-plugin', []);

        // $locale = 'fr_FR';
        // $this->assertEquals('fr_FR', $locale);
        // $this->assertEquals('fr_FR', get_locale());

        // $success = $this->rbp->load_textdomains();
        // $this->assertTrue($success);
        $this->assertEquals('Bienvenue, Dummy', __('WELCOME_DUMMY', 'rbp-test-plugin'));
        $this->assertEquals('Base de Plugin WordPress par T1z', __('WPPC_PLUGIN_NAME', 'bhubr-wprbp'));

        unload_textdomain('rbp-test-plugin');
        $this->assertEquals('WELCOME_DUMMY', __('WELCOME_DUMMY', 'rbp-test-plugin'));
    }

    /**
     * Ensure that text domain is properly loaded with default locale (en_US)
     */
    function test_load_textdomains_default() {
        global $locale;
        $this->rbp->register_plugin('rbp-test-plugin', []);

        $locale = 'en_US';
        $success = $this->rbp->load_textdomains();
        $this->assertTrue($success);
        $this->assertEquals('Welcome, Dummy', __('WELCOME_DUMMY', 'rbp-test-plugin'));

        unload_textdomain('rbp-test-plugin');
        $this->assertEquals('WELCOME_DUMMY', __('WELCOME_DUMMY', 'rbp-test-plugin'));
    }

    /**
     * Ensure that plugin custom post types are registered, with correct labels
     */
    function test_register_post_types() {
        global $locale;
        $locale = 'fr_FR';
        // $this->rbp->register_plugin('dummy-plugin', [
        //     'types' => [
        //         'dummy_type' => [  // key is type name (singular, lower-case)
        //             'name_s'   => 'DUMMY_TYPE',  // label (singular)
        //             'fields'   => ['foo', 'bar', 'baz'],
        //             'taxonomies' => [
        //                 'bar_cat' => [
        //                     'name_s' => 'BAR_CAT',
        //                     'fields' => ['baaar', 'caaat']
        //                 ],
        //                 'boo_tag' => [
        //                     'name_s' => 'BOO_TAG',
        //                     'fields' => ['foooo', 'taaag']
        //                 ]
        //             ]
        //         ]
        //     ]
        // ]);
        // $success = $this->rbp->load_textdomains();
        // $this->assertTrue($success);

        $this->rbp->register_types();

        $builtin_types = get_post_types(['_builtin' => true]);
        // foo type already registered by test plugin
        $all_types = array_merge($builtin_types, ['foo' => 'foo', 'dummy_type' => 'dummy_type']);
        $this->assertEquals( $all_types, get_post_types() );

        $type_objects = get_post_types(['_builtin' => false], 'objects');
        $type_labels = $type_objects['dummy_type']->labels;
        $this->assertEquals($type_labels->name, "Stupide Types");
        $this->assertEquals($type_labels->singular_name, "Stupide Type");
        $this->assertEquals($type_labels->add_new, "Ajouter");
        $this->assertEquals($type_labels->add_new_item, "Ajouter Stupide Type");
        $this->assertEquals($type_labels->new_item, "Nouveau Stupide Type");
        $this->assertEquals($type_labels->view_item, "Voir Stupide Type");
        $this->assertEquals($type_labels->search_items, "Rechercher Stupide Types");
        $this->assertEquals($type_labels->not_found, "Non trouvé");
        $this->assertEquals($type_labels->not_found_in_trash, "Aucun élément dans la Corbeille");
    }

}
