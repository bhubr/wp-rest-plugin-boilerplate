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

        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance();
        $this->rpb->register_plugin('wprbp-test-foo', MODELS_DIR . '/foo');

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
        $this->request_get('/foos', 200, []);

        $model1 = bhubr\Foo::create(['name' => 'Pouet']);
        $model2 = bhubr\Foo::create(['name' => 'Youpla boum']);
        $this->request_get('/foos', 200, [
            ['id' => 3, 'name' => 'Pouet', 'slug' => 'pouet', 'foo_cat' => null, 'foo_tags' => []],
            ['id' => 4, 'name' => 'Youpla boum', 'slug' => 'youpla-boum', 'foo_cat' => null, 'foo_tags' => []],
        ]);
    }

}