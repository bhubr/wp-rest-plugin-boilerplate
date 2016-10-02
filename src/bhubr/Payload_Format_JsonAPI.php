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

    protected static function extract_relationship_data_single($relation_type, $data) {
        $data_type = $data['type'];
        if($data_type !== $relation_type) throw new \Exception(
            "Relationship type mismatch (exp: $relation_type, got: $data_type)",
            Payload_Format::RELATIONSHIP_BAD_TYPE
        );
        // var_dump("extract $relation_type " . $data['id']);
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
        // var_dump($payload_relationships);
        foreach($model_relationships as $relation_name => $descriptor) {
            // if (! array_key_exists($relationship, $payload)) continue;
            $relation_item = $payload_relationships[$relation_name];
            // if(! array_key_exists('data', $values)) {
            //     $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
            //     throw new \Exception($msg, Payload_Format::RELATIONSHIP_JSONAPI_EXPECTS_DATA);
            // }
            self::fail_if_key_not_found(
                'data',
                $relation_item,
                "/data/relationships/$relation_name/data",
                Payload_Format::JSONAPI_MISSING_DATA
            );

            $relation_data = $relation_item['data'];

            // echo "found $relation_name\n";
            // var_dump($descriptor);
            // var_dump($relation_data);

            if(! $descriptor['plural'] ) {
                if (! array_key_exists('type', $relation_data) || ! array_key_exists('id', $relation_data)) {
                    $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                    throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_SINGULAR);
                }
                $extracted_data = self::extract_relationship_data_single($descriptor['type'], $relation_data);
                echo "singular\n";
                var_dump($extracted_data);
            }
            
            else {
                if(array_key_exists('type', $relation_data) || array_key_exists('id', $relation_data)) {
                    $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
                    throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_PLURAL);
                }
                $extracted_data = self::extract_relationship_data_multi($descriptor['type'], $relation_data);
                echo "plural\n";
                var_dump($extracted_data);
                
            }
                // array_key_exists('type', $relation_data)
                
                // ( array_key_exists('type', $relation_data) || array_key_exists('id', $relation_data) )  
            $relationships[$relation_name] = $extracted_data;
        }
        unset($payload['data']['relationships']);
        return [
            'relationships' => $relationships,
            'payload' => $payload
        ];
    }

    public static function extract_attributes($payload, $model_attributes) {

    }

}