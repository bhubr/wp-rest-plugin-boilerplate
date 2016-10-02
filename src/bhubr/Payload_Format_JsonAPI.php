<?php
namespace bhubr;

class Payload_Format_JsonAPI extends Payload_Format_Common implements Payload_Format_Interface {

    public static function fail_if_key_not_found($key, $array, $error_source, $error_code) {
        if(! array_key_exists($key, $array)) {
            throw new \Exception("Payload missing: $error_source", $error_code);
        }

    }
    // public static function parse_and_validate($payload, $model_attributes, $model_relationships) {

    // }

    protected static function extract_relationship_data_single($relation_type, $data) {
        $data_type = $data['type'];
        if($data_type !== $relation_type) throw new \Exception(
            "Relationship type mismatch (exp: $relation_type, got: $data_type)",
            Payload_Format::RELATIONSHIP_BAD_TYPE
        );
        return $data['id'];
    }

    protected static function extract_relationship_data_multi($relation_type, $data) {
        return array_map(
            function($item) use($relation_type) {
                return self::extract_relationship_data_single($relation_type, $item);
            }, $data
        );
    }

    public static function extract_relationships($payload, $model_relationships) {
        $relationships = [];
        self::fail_if_key_not_found(
            'data', $payload, '/data', Payload_Format::JSONAPI_MISSING_DATA
        );
        if (!array_key_exists('relationships', $payload['data'])) {
            return [
                'relationships' => [],
                'payload' => $payload
            ];
        }
        $payload_relationships = $payload['data']['relationships'];
        if( ! is_array($payload_relationships)) {
            throw new \Exception(
                "Payload format: /data/relationships",
                Payload_Format::JSONAPI_RELATIONSHIPS_NOT_ARRAY
            );
        }
        foreach($model_relationships as $relation_name => $descriptor) {
            if (! array_key_exists($relation_name, $payload_relationships)) continue;
            $relation_item = $payload_relationships[$relation_name];
            self::fail_if_key_not_found(
                'data',
                $relation_item,
                "/data/relationships/$relation_name/data",
                Payload_Format::JSONAPI_MISSING_DATA
            );

            $relation_data = $relation_item['data'];

            if(! $descriptor['plural'] ) {
                if (is_null($relation_data)) {
                    $extracted_data = null;
                }
                else if (is_array($relation_data) && empty($relation_data)) {
                    throw new \Exception("Invalid relationship clear data: singular relationship '$relation_name' expects null, got []", Payload_Format::RELATIONSHIP_INVALID_CLEAR);
                }
                else if (! is_array($relation_data) || ! array_key_exists('type', $relation_data) || ! array_key_exists('id', $relation_data)) {
                    $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                    throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_SINGULAR);
                }
                else {
                    $extracted_data = self::extract_relationship_data_single($descriptor['type'], $relation_data);
                }
            }
            else {
                if (is_null($relation_data)) {
                    throw new \Exception("Invalid relationship clear data: plural relationship '$relation_name' expects [], got null", Payload_Format::RELATIONSHIP_INVALID_CLEAR);
                }
                if(array_key_exists('type', $relation_data) || array_key_exists('id', $relation_data)) {
                    $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
                    throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_PLURAL);
                }
                $extracted_data = self::extract_relationship_data_multi($descriptor['type'], $relation_data);
            }
            $relationships[$relation_name] = $extracted_data;
        }
        unset($payload['data']['relationships']);
        return [
            'relationships' => $relationships,
            'payload' => $payload
        ];
    }

    public static function check_and_extract_attributes($payload, $model_attributes) {

    }

}