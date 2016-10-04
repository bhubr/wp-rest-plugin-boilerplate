<?php
namespace rel;
use bhubr\REST\Model\Post;

class Person extends Post {
    static $type = 'post';

    static $singular = 'person';
    static $plural = 'persons';

    static $name_s = 'Person';
    static $name_p = 'Persons';

    static $fields = [
        'first_name'   => [
            'type'     => 'string',
            'required' => 'true'
        ],
        'last_name'    => [
            'type'     => 'string',
            'required' => 'true'
        ],
        'email'        => [
            'type'     => 'string'
        ],
        'birth_year'   => [
            'type'     => 'integer'
        ]
    ];
    static $relations = [
        'mybooks'      => 'rel\Book:has_many',
        'mypass'       => 'rel\Passport:has_one:owner'
    ];

}