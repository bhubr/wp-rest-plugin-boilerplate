<?php
 /**
  * @package bhubr\REST
  */
namespace bhubr\REST\Model;

class Relationships {
    public static function parse_for_model($relationships) {
        return $relationships->map(['bhubr\REST\Model\Relationships', 'parse_relationship']);
    }

    public static function parse_relationship($relationship_descriptor, $relationship_attr) {
        $desc_bits = explode(':', $relationship_descriptor);
        $rel_class = $desc_bits[0];
        $rel_type = $desc_bits[1];
        $output = [
            'type'     => $rel_class::$plural,
            'plural'   => array_search($rel_type, ['has_one', 'belongs_to']) === false,
            'rel_type' => $desc_bits[1],
        ];
        if( count( $desc_bits ) > 2 ) $output['inverse'] = $desc_bits[2];
        return collect_f($output);
    }
}