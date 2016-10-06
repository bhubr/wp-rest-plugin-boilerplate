<?php

/**
 * Inspiration for this test: https://pantheon.io/blog/test-coverage-your-wp-rest-api-project
 * Eternal kudos to Daniel Bachhuber (https://twitter.com/danielbachhuber)
 */
require_once 'Backend_Request_and_Assert.php';

class Test_REST_Backend extends WPRPB_UnitTestCase {
    use Backend_Request_and_Assert;
  
    public function setUp() {
        parent::setUp();

        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;

        $this->rpb->register_plugin('dummy-plugin', RESOURCES_DIR, [
            'models_dir' => 'models/relationships',
            'models_namespace' => 'rel\\',
        ]);
        do_action( 'init' );
        do_action( 'rest_api_init' );

    }

    public function tearDown() {
        parent::tearDown();
       
        global $wp_rest_server;
        $wp_rest_server = null;
    }


    /**
     * CREATE NOT OK
     * Missing & invalid fields
     */
    public function test_create_nok_missing_fields() {
        $this->request_post('/persons', [
            'birth_year' => 1967,
            'last_name'  => 777,
            'unknown'    => '!! BAD FIELD: NOT DECLARED IN MODEL !!'
        ], 400, [
            'error' => "Missing or invalid fields (missing:first_name, invalid: last_name)"
        ]);
    }


    /**
     * CREATE OK
     * 'ID' and 'unknown' fields provided in POST request are to be ignored
     */
    public function test_create_ok_remove_unknown_field() {
        $this->request_post('/persons', [
            'ID'         => 511,
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'johndoe@example.com',
            'birth_year' => 1967,
            'unknown'    => '!! BAD FIELD: NOT DECLARED IN MODEL !!'
        ], 200, [
            'id'         => 1,
            'name'       => 'John Doe',
            'slug'       => 'john-doe',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'johndoe@example.com',
            'birth_year' => 1967,
        ]);
    }

    /**
     * CREATE with 1-1 relationship OK
     */
    public function test_create_ok_relationship_one_to_one() {
        $this->request_post('/persons', [
            'first_name' => 'Foo',
            'last_name'  => 'Bar',
            'email'      => 'foobar@example.com',
            'birth_year' => 1977,
            // 'mypass'     => [
            //     'country_code' => 'uk',
            //     'date_issued'  => '2014-11-19',
            //     'number'       => 'T7820-AXB-102'
            // ]
        ], 200, [
            'id'         => 1,
            'name'       => 'Foo Bar',
            'slug'       => 'foo-bar',
            'first_name' => 'Foo',
            'last_name'  => 'Bar',
            'email'      => 'foobar@example.com',
            'birth_year' => 1977,
        ]);
        $this->request_post('/passports', [
            'country_code' => 'uk',
            'date_issued'  => '2014-11-19',
            'number'       => 'T7820-AXB-102',
            'owner'        => 1
        ], 200, [
            'id'           => 2,
            'name'         => 'uk-T7820-AXB-102',
            'slug'         => 'uk-t7820-axb-102',
            'country_code' => 'uk',
            'date_issued'  => '2014-11-19',
            'number'       => 'T7820-AXB-102',
            'owner'        => 1
        ]);
        $this->request_get('/persons/1/mypass', 200,
            [
                'id'           => 2,
                'name'         => 'uk-T7820-AXB-102',
                'slug'         => 'uk-t7820-axb-102',
                'country_code' => 'uk',
                'date_issued'  => '2014-11-19',
                'number'       => 'T7820-AXB-102',
                // 'owner'        => 1
            ]
        );
        $this->request_get('/passports/3/owner', 404,
            [
                'error' => 'Post with id=3 was not found',
            ]
        );
        $this->request_get('/passports/2/owner', 200,
            [
                'id'         => 1,
                'name'       => 'Foo Bar',
                'slug'       => 'foo-bar',
                'first_name' => 'Foo',
                'last_name'  => 'Bar',
                'email'      => 'foobar@example.com',
                'birth_year' => 1977,
                // 'mypass'     => 2
            ]
        );

    }

    /**
     * CREATE with 1-1 relationship NOK (OWNER not found)
     */
    public function test_create_nok_relationship_one_to_one() {
        $this->request_post('/persons', [
            'first_name' => 'Foo', 'last_name'  => 'Bar', 'email' => 'foobar@example.com',
        ], 200, [
            'id'         => 1,
            'name'       => 'Foo Bar',
            'slug'       => 'foo-bar',
            'first_name' => 'Foo',
            'last_name'  => 'Bar',
            'email'      => 'foobar@example.com',
        ]);
        // TODO DELETE LE PASSPORT?????
        $this->request_post('/passports', [
            'country_code' => 'uk',
            'date_issued'  => '2014-11-19',
            'number'       => 'T7820-AXB-102',
            'owner'        => 101
        ], 400, [
            'error' => 'Cannot link rel\Passport(id=2) with non-existent rel\Person(id=101)'
        ]);
        global $wpdb;
        $res = $wpdb->get_results(
            "SELECT * FROM {$this->pivot_table}"
        );
        var_dump($res);
    }

    public function test_get() {
        global $wpdb;
        $res = $wpdb->get_results(
            "SELECT * FROM {$this->pivot_table}"
        );
        $this->assertEquals(0, count($res));
        $posts = get_posts();
        $this->assertEquals(0, count($posts));

        $res = $wpdb->get_results(
            "SELECT * FROM {$wpdb->postmeta}"
        );

        // $folder = realpath(__DIR__ . '/../../'); // Répertoire où sauvegarder le dump de la base de données
        // $cmd = sprintf("mysqldump -u%s -p%s %s > %s/%s", DB_USER,DB_PASSWORD,DB_NAME,$folder,DB_NAME."-".date("d-m-Y-H\hi").".sql"); 
        // system($cmd);

        $this->assertEquals(0, count($res));

        $this->request_get('/persons', 200, []);

        $model1    = rel\Person::create(['first_name' => 'Harry', 'last_name' => 'Potter']);
        $this->assertEquals(1, $model1['id']);
        $model2    = rel\Person::create(['first_name' => 'Sally', 'last_name' => 'Harper']);
        $this->assertEquals(2, $model2['id']);
        $passport1 = rel\Passport::create(['name' => "HP's pass", 'country_code' => 'fr', 'number' => 'XYZ666', 'date_issued' => '2016-09-18']);
        $this->assertEquals(3, $passport1['id']);
        $passport2 = rel\Passport::create(['name' => "SH's pass", 'country_code' => 'fr', 'number' => 'ZYX999', 'date_issued' => '2015-04-26']);
        $this->assertEquals(4, $passport2['id']);

        $this->request_get('/persons', 200, [
            ['id' => 1, 'first_name' => 'Harry', 'last_name' => 'Potter', 'slug' => 'harry-potter', 'name' => 'Harry Potter'],
            ['id' => 2, 'first_name' => 'Sally', 'last_name' => 'Harper', 'slug' => 'sally-harper', 'name' => 'Sally Harper'],
        ]);

        $this->request_get('/passports', 200, [
            ['id' => 3, 'name' => 'fr-XYZ666', 'slug' => 'fr-xyz666', 'country_code' => 'fr', 'number' => 'XYZ666', 'date_issued' => '2016-09-18'],
            ['id' => 4, 'name' => "fr-ZYX999", 'slug' => 'fr-zyx999', 'country_code' => 'fr', 'number' => 'ZYX999', 'date_issued' => '2015-04-26'],
        ]);


        $pivot_table = $wpdb->prefix . 'rpb_many_to_many';
        $data = [
            'rel_type'   => 'person_passport',
            'object1_id' => 1,
            'object2_id' => 3
        ];
        $res = $wpdb->insert($pivot_table, $data, ['%s', '%d', '%d']);
        if(!$res) throw Exception(vsprintf("unable to create rel %s %d %d", $data));
        $data = [
            'rel_type'   => 'person_passport',
            'object1_id' => 2,
            'object2_id' => 4
        ];
        $res = $wpdb->insert($pivot_table, $data, ['%s', '%d', '%d']);
        if(!$res) throw Exception(vsprintf("unable to create rel %s %d %d", $data));


        $this->request_get('/passports/3/owner', 200,
            ['id' => 1, 'first_name' => 'Harry', 'last_name' => 'Potter', 'name' => 'Harry Potter', 'slug' => 'harry-potter']
        );
        $this->request_get('/persons/1/mypass', 200,
            ['id' => 3, 'name' => 'fr-XYZ666', 'slug' => 'fr-xyz666', 'country_code' => 'fr', 'number' => 'XYZ666', 'date_issued' => '2016-09-18']
        );
        $this->request_get('/persons/2/mypass', 200,
            ['id' => 4, 'name' => "fr-ZYX999", 'slug' => 'fr-zyx999', 'country_code' => 'fr', 'number' => 'ZYX999', 'date_issued' => '2015-04-26']
        );
    }

}