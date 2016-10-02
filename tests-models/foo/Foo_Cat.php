<?php
namespace bhubr;

class Foo_Cat extends Term_Model {
    static $type = 'term';
    static $post_type = 'foo';

    static $singular = 'foo_cat';
    static $plural = 'foo_cats';

    static $name_s = 'Foo Cat';
    static $name_p = 'Foo Cats';

    static $fields = [
    ];
    static $relations = [
    ];

}