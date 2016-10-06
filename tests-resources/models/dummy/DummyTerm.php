<?php
namespace bhubr;
use bhubr\REST\Model\Term;

class DummyTerm extends Term {
    static $type = 'term';
    static $post_type = 'dummy';

    static $singular = 'dummyterm';
    static $plural = 'dummyterms';

    static $name_s = 'DummyTerm';
    static $name_p = 'DummyTerms';

    static $fields = [
        'type'          => 'string',
        'status'        => 'string',
        'dummy_int'     => 'integer',
        'dummy_str'     => 'string',
        'termone_id'    => 'Termone:has_one'
    ];
    static $relations = [
        'termone'        => 'bhubr\Termone:has_one',
        'termanies'      => 'bhubr\Termany:has_many',
        'termany2manies' => 'bhubr\Termany2many:has_many',
        'dummies'        => 'bhubr\Dummy:has_many'
    ];

}