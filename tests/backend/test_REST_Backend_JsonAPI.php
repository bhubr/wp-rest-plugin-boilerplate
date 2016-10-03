<?php

/**
 * Inspiration for this test: https://pantheon.io/blog/test-coverage-your-wp-rest-api-project
 * Eternal kudos to Daniel Bachhuber (https://twitter.com/danielbachhuber)
 */
use bhubr\REST\Payload\Formatter;

/*

*/
/*{
  "links": {
    "self": "http://example.com/articles"
  },
  "data": [{
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "JSON API paints my bikeshed!"
    }
  }, {
    "type": "articles",
    "id": "2",
    "attributes": {
      "title": "Rails is Omakase"
    }
  }]
}*/
class Test_REST_Backend_JsonAPI extends WP_UnitTestCase {
  
    public function setUp() {
        parent::setUp();
       
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;

        $this->rpb = bhubr\REST\Plugin_Boilerplate::get_instance();
        $this->rpb->register_plugin('dummy-plugin', RESOURCES_DIR, [
            'models_dir' => 'models/foo',
            'models_namespace' => 'foo\\'
        ]);

        $this->rpb->register_plugin('wprbp-test-json-api', RESOURCES_DIR, [
            'models_dir' => 'models/json-api',
            'rest_type'  => Formatter::JSONAPI
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
      var_dump($response->data);
      $this->assertEquals( $expected_data, $response->data );
    }

    public function test_get() {
        $this->request_get('/articles', 200, []);
        $expected = '{"links":{"self":"http://example.com/articles"},"data":[{"type":"articles","id":"3","attributes":{"name":"WordPress REST rocks!"}},{"type":"articles","id":"4","attributes":{"name":"Rails is Omakase"}}]}';
        $parsed = json_decode($expected, true);

        $model1 = bhubr\Article::create(['name' => 'WordPress REST rocks!']);
        // $this->request_get('/articles', 200, [
        //     ['id' => 3, 'name' => 'WordPress REST rocks!', 'slug' => 'wordpress-rest-rocks' //, 'foo_cat' => null, 'foo_tags' => []],
        // ]);
        $this->request_get('/articles', 200, $parsed);

    }

}
