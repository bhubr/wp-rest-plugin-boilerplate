<?php
namespace bhubr;

class Payload_Format_JsonAPI implements Payload_Format_Interface {

    public static function fail_if_key_not_found($key, $array, $error_source, $error_code) {
        if(! array_key_exists($key, $array)) {
            throw new \Exception("Payload format: $error_source", $error_code);
        }

    }
    public static function parse_and_validate($payload, $model_attributes, $model_relationships) {

    }

    public static function extract_relationships($payload, $model_relationships) {
        $relationships = [];
        self::fail_if_key_not_found('data', $payload, '/data', Payload_Format::JSONAPI_MISSING_DATA);
        if (!array_key_exists('relationships', $payload['data'])) {
            return [
                'relationships' => [],
                'payload' => $payload
            ];
        }
        $payload_relationships = $payload['data']['relationships'];
        if( ! is_array($payload_relationships)) {
            throw new \Exception("Payload format: /data/relationships", Payload_Format::JSONAPI_RELATIONSHIPS_NOT_ARRAY);
        }
        foreach($model_relationships as $relationship => $descriptor) {
            if (! array_key_exists($relationship, $payload)) continue;
            $values = $payload_relationships[$relationship];
            if(! array_key_exists('data', $values)) {
                $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                throw new \Exception($msg, Payload_Format::RELATIONSHIP_JSONAPI_EXPECTS_DATA);
            }

            // if(! $descriptor['plural'] && !array_key_exists(key, array)) {
            //     $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
            //     throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_SINGULAR);
            // }
            // else if($descriptor['plural'] && ! is_array($values)) {
            //     $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
            //     throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_PLURAL);
            // }
            $relationships[$relationship] = $payload[$relationship];
            unset($payload[$relationship]);
        }
        return [
            'relationships' => $relationships,
            'payload' => $payload
        ];
    }

    public static function extract_attributes($payload, $model_attributes) {

    }

}