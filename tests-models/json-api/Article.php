<?php
namespace bhubr;

class Article extends Post_Model {
    static $type = 'post';

    static $singular = 'article';
    static $plural = 'articles';

    static $name_s = 'Article';
    static $name_p = 'Articles';

    static $fields = [
        'title'  => [
            'type'     => 'string',
            'required' => 'true'
        ] 
    ];
    static $relations = [
    ];

}