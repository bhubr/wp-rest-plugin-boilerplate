<?php
namespace bhubr;
use bhubr\REST\Model\Post;

class Article extends Post {
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