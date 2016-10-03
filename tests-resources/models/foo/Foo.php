<?php
namespace foo;
use bhubr\REST\Model\Post;

class Foo extends Post {
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
        'categories' => 'foo\Foo_Cat:has_many',
        'tags'       => 'foo\Foo_Tag:has_many'
    ];

}