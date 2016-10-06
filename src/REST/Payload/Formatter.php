<?php
namespace bhubr\REST\Payload;

class Formatter {
    const SIMPLE   = '_Simple';
    const JSONAPI  = '_JsonAPI';
    const JSEND    = '_JSend';

    const INVALID_PAYLOAD_FORMAT   = 100;
    const RELATIONSHIP_IS_SINGULAR = 200;
    const RELATIONSHIP_IS_PLURAL   = 201;
    const RELATIONSHIP_BAD_TYPE    = 202;
    const RELATIONSHIP_INVALID_CLEAR = 203;

    const JSONAPI_MISSING_DATA            = 99;
    const JSONAPI_RELATIONSHIPS_NOT_ARRAY = 98;

    public static function process_payload($payload_format, $payload, $model_descriptor) {
        $model_attributes    = $model_descriptor->get_f('attributes');
        $model_relationships = $model_descriptor->get_f('relationships');
        $model_class_name    = $model_descriptor->get_f('class');
        $accepted_payload_formats = [self::SIMPLE, self::JSONAPI]; //, self::JSEND]; JSEND not implemented
        if ( array_search($payload_format, $accepted_payload_formats ) === false) {
            $msg = "Invalid payload format $payload_format. Valid formats: ";
            throw new \Exception($msg. implode(', ', $accepted_payload_formats), self::INVALID_PAYLOAD_FORMAT);
        }
        $strategy_class = 'bhubr\REST\Payload\Formatter' . $payload_format;
        // return $strategy_class::parse($payload, $attributes_keys, $relationships_keys);
        $result_relationships = $strategy_class::extract_relationships($payload, $model_relationships);
        $result_attributes = $strategy_class::check_and_extract_attributes($result_relationships['payload'], $model_attributes);
        // $mapped_attributes = $model_class_name::map_fields_payload_to_wp($result_attributes['attributes']);
        return [
            'relationships' => $result_relationships['relationships'],
            'attributes'    => $result_attributes['attributes'],
            'unknown_attrs' => $result_attributes['unknown'],
        ];
    }

}