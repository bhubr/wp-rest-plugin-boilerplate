<?php
namespace bhubr;

interface Payload_Format_Interface {
    // public static function parse_and_validate($payload, $model_attributes, $model_relationships);
    public static function extract_relationships($payload, $model_relationships);
    public static function check_and_extract_attributes($payload, $model_attributes);
}