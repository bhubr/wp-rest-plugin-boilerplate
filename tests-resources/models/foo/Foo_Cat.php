<?php
namespace foo;
use bhubr\REST\Model\Term;

class Foo_Cat extends Term {
    static $type = 'term';
    static $post_type = 'foo';

    static $singular = 'foo_cat';
    static $plural = 'foo_cats';

    static $name_s = 'Foo Cat';
    static $name_p = 'Foo Cats';

    static $fields = [
    ];
    static $relations = [
        'foos' => 'foo\Foo:has_many',
    ];

}