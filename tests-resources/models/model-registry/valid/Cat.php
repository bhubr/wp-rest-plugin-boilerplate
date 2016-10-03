<?php
namespace registrytest\valid;
use bhubr\REST\Model\Term;

class Cat extends Term {
    static $type = 'term';
    static $post_type = 'valid_type';

    static $singular = 'valid_cat';
    static $plural = 'valid_cats';

    static $name_s = 'Valid Cat';
    static $name_p = 'Valid Cats';

    static $fields = [
        'some_attr'  => [
            'type'     => 'string',
            'required' => 'true'
        ] 
    ];
    static $relations = [
    ];

}