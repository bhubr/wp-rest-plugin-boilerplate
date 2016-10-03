<?php
namespace registrytest\invalid;
use bhubr\REST\Model\Term;

class MissingPropsCat extends Term {
    static $type = 'term';

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