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
        'termone'        => 'Termone:has_one',
        'termanies'      => 'Termany:has_many',
        'termany2manies' => 'Termany2many:has_many',
        'dummies'        => 'Dummy:has_many'
    ];

}