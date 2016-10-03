<?php
namespace registrytest\valid;
use bhubr\REST\Model\Post;

class Type extends Post {
    static $type = 'post';

    static $singular = 'valid_type';
    static $plural = 'valid_types';

    static $name_s = 'Valid Type';
    static $name_p = 'Valid Types';

    static $fields = [
        'title'  => [
            'type'     => 'string',
            'required' => 'true'
        ] 
    ];
    static $relations = [
    ];

}