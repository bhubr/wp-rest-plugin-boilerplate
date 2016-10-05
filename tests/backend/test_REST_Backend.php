<?php

/**
 * Inspiration for this test: https://pantheon.io/blog/test-coverage-your-wp-rest-api-project
 * Eternal kudos to Daniel Bachhuber (https://twitter.com/danielbachhuber)
 */

class Test_REST_Backend extends WP_UnitTestCase {
  
    public function setUp() {
        parent::setUp();
       
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;

        $this->rpb = bhubr\REST\Plugin_Boilerplate::get_instance();
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

    protected function request_get($url, $expected_status, $expected_data) {
        $request = new WP_REST_Request( 'GET', '/bhubr/v1' . $url );
        $response = $this->server->dispatch( $request );
        $this->assertEquals( $expected_status, $response->status );
        $this->assertEquals( $expected_data, $response->data );
    }

    public function test_get() {
        $this->request_get('/persons', 200, []);

        $model1    = rel\Person::create(['name' => 'Harry Potter']);
        $model2    = rel\Person::create(['name' => 'Sally Harper']);
        $passport1 = rel\Passport::create(['name' => "HP's pass", 'country_code' => 'fr', 'number' => 'XYZ666']);
        $passport2 = rel\Passport::create(['name' => "SH's pass", 'country_code' => 'fr', 'number' => 'ZYX999']);

        $this->request_get('/persons', 200, [
            ['id' => 3, 'name' => 'Harry Potter', 'slug' => 'harry-potter'],
            ['id' => 4, 'name' => 'Sally Harper', 'slug' => 'sally-harper'],
        ]);

        $this->request_get('/passports', 200, [
            ['id' => 5, 'name' => "HP's pass", 'slug' => 'hps-pass', 'country_code' => 'fr', 'number' => 'XYZ666'],
            ['id' => 6, 'name' => "SH's pass", 'slug' => 'shs-pass', 'country_code' => 'fr', 'number' => 'ZYX999'],
        ]);

        global $wpdb;
        $pivot_table = $wpdb->prefix . 'rpb_many_to_many';
        echo "\n\n#### INSERT ENTRY into pivot table\n\n";
        $wpdb->show_errors();
        $data = [
            'rel_type'   => 'person_passport',
            'object1_id' => 3,
            'object2_id' => 5
        ];
        $res = $wpdb->insert($pivot_table, $data, ['%s', '%d', '%d']);


        $res = $wpdb->get_results(
            "SELECT * FROM {$pivot_table} WHERE REL_TYPE='person_passport' AND object2_id = 5", ARRAY_A
        );
        echo "\n READ ENTRY results\n";
        var_dump($res);
        // var_dump($res);
        if(empty($res)) $wpdb->print_error();


        // $this->request_get('/passports/5', 200,
        //     ['id' => 5, 'name' => "HP's pass", 'slug' => 'hps-pass', 'country_code' => 'fr', 'number' => 'XYZ666']
        // );
        // $request = new WP_REST_Request( 'GET', '/bhubr/v1/passports/5/owner'  );
        // $response = $this->server->dispatch( $request );
        $this->request_get('/passports/5/owner', 200,
            ['id' => 3, 'name' => 'Harry Potter', 'slug' => 'harry-potter']
        );

        $this->request_get('/persons/3/mypass', 200,
            ['id' => 5, 'name' => "HP's pass", 'slug' => 'hps-pass', 'country_code' => 'fr', 'number' => 'XYZ666']
        );
    }

}