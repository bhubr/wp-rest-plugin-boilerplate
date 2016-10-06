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
class Test_Payload_Format extends WP_UnitTestCase {
    /**
     * Error: invalid payload format
     * @expectedException Exception
     * @expectedExceptionCode bhubr\REST\Payload\Formatter::INVALID_PAYLOAD_FORMAT
     */
    function test_nok_parse_invalid_payload_format() {
        $data = Formatter::process_payload(-1, [], [], []);
    }
}
