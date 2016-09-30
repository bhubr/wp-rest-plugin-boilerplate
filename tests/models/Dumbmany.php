<?php
namespace bhubr;

class Dumbmany extends Post_Model {
    static $type = 'post';

    static $singular = 'dumbmany';
    static $plural = 'dumbmanies';

    static $name_s = 'Dumbmany';
    static $name_p = 'Dumbmanies';

    static $fields = [
        'dumb_str'  => 'string',
        'dummy_id'  => 'Dummy:belongs_to'
    ];
    static $relations = [
        'dummy'    => 'Dummy:belongs_to'
    ];
}