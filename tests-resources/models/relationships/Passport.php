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
    static $map_functions = [
        'name' => [__CLASS__, 'map_name']
    ];
    static function map_name( $attributes ) { return $attributes->get('country_code') . '-' . $attributes->get('number'); }

    static $relations = [
        'owner'    => 'rel\Person:belongs_to:mypass'
    ];

}