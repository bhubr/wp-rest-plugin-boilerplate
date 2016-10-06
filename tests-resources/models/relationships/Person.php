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
        'first_name'    => [
            'type'      => 'string',
            'required'  => 'true',
            'validator' => 'alpha'
        ],
        'last_name'     => [
            'type'      => 'string',
            'required'  => 'true',
            'validator' => 'alpha'
        ],
        'email'         => [
            'type'      => 'string'
        ],
        'birth_year'    => [
            'type'      => 'integer'
        ]
    ];
    // static $map_fields = ['post_title' => 'first_name'];
    static $map_functions = [
        'name' => [__CLASS__, 'map_name']
    ];
    static function map_name( $attributes ) { return $attributes->get('first_name') . ' ' . $attributes->get('last_name'); }
    static $relations = [
        'mybooks'      => 'rel\Book:has_many:author',
        'mypass'       => 'rel\Passport:has_one:owner'
    ];

}