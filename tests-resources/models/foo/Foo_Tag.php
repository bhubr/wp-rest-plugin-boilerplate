<?php
namespace foo;
use bhubr\REST\Model\Term;

class Foo_Tag extends Term {
    static $type = 'term';
    static $post_type = 'foo';

    static $singular = 'foo_tag';
    static $plural = 'foo_tags';

    static $name_s = 'Foo Tag';
    static $name_p = 'Foo Tags';

    static $fields = [
    ];
    static $relations = [
        'foos' => 'foo\Foo:has_many',
    ];

}