<?php
namespace rel;
use bhubr\REST\Model\Post;

class Passport extends Post {
    static $type = 'post';

    static $singular = 'passport';
    static $plural = 'passports';

    static $name_s = 'Passport';
    static $name_p = 'Passports';

    static $fields = [
        'country_code' => [
            'type'     => 'string',
            'required' => 'true'
        ],
        'number'       => [
            'type'     => 'string',
            'required' => 'true'
        ],
        'date_issued'  => [
            'type'     => 'string',
            'required' => 'true'
        ]
    ];
    static $relations = [
        'books'    => 'rel\Book:has_many',
        'owner'    => 'rel\Person:belongs_to:mypass'
    ];

}