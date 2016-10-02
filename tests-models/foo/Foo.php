<?php
namespace bhubr;

class Foo extends Post_Model {
    static $type = 'post';

    static $singular = 'foo';
    static $plural = 'foos';

    static $name_s = 'Foo';
    static $name_p = 'Foos';

    static $fields = [
        'foo_type'   => 'string',
        'foo_number' => 'integer',
        'foo_cat'    => 'taxonomy_term',
        'foo_tags'   => 'taxonomy_term'
    ];
    static $relations = [
    ];

}