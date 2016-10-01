<?php
namespace bhubr;

interface Payload_Format_Interface {
    public static function parse($payload, $attributes_keys, $relationships_keys);
}