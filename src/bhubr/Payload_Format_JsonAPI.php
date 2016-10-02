<?php
namespace bhubr;

class Payload_Format_JsonAPI implements Payload_Format_Interface {

    public static function fail_if_key_not_found($key, $array, $error_source, $error_code) {
        if(! array_key_exists($key, $array)) {
            throw new \Exception("Payload missing: $error_source", $error_code);
        }

    }
    public static function parse_and_validate($payload, $model_attributes, $model_relationships) {

    }

    // protected static function extract_relationship_data($relation_name, $data) {

    // }

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
        // var_dump($payload_relationships);
        foreach($model_relationships as $relation_name => $descriptor) {
            // if (! array_key_exists($relationship, $payload)) continue;
            $relation_item = $payload_relationships[$relation_name];
            // if(! array_key_exists('data', $values)) {
            //     $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
            //     throw new \Exception($msg, Payload_Format::RELATIONSHIP_JSONAPI_EXPECTS_DATA);
            // }
            self::fail_if_key_not_found('data', $relation_item, "/data/relationships/$relation_name/data", Payload_Format::JSONAPI_MISSING_DATA);

            $relation_data = $relation_item['data'];

            echo "found $relation_name\n";
            var_dump($descriptor);
            var_dump($relation_data);

            if(! $descriptor['plural'] && 
                (! array_key_exists('type', $relation_data) || ! array_key_exists('id', $relation_data))
            ) {
                $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_SINGULAR);
            }
            
            else if( $descriptor['plural'] &&
                // array_key_exists('type', $relation_data)
                (array_key_exists('type', $relation_data) || array_key_exists('id', $relation_data))
                // ( array_key_exists('type', $relation_data) || array_key_exists('id', $relation_data) )  
            ) {
                $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
                throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_PLURAL);
            }
            // $relationships[$relation_name] = $payload[$relation_name];
            unset($payload['data']['relationships'][$relation_name]);
        }
        return [
            'relationships' => $relationships,
            'payload' => $payload
        ];
    }

    public static function extract_attributes($payload, $model_attributes) {

    }

}