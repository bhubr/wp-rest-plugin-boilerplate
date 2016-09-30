<?php
namespace bhubr;

class Dumbass extends Post_Model {
    static $type = 'post';

    static $singular = 'dumbass';
    static $plural = 'dumbasses';

    static $name_s = 'Dumbass';
    static $name_p = 'Dumbasses';

    static $fields = [
        'dumb_str'  => 'string',
        'dummy_id'  => 'Dummy:belongs_to'
    ];
    static $relations = [
        'dummy'    => 'Dummy:belongs_to'
    ];

}