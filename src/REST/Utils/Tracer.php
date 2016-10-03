<?php
namespace bhubr\REST\Utils;

class Tracer {
    static $registry = [];

    public static function add($key, $trace) {
        if (!array_key_exists($key, self::$registry)) {
            self::$registry[$key] = [];
        }
        self::$registry[$key][] = $trace;
    }

    public static function save($class_name, $method_name) {
        if (! defined('WPRDB_DEBUG') || WPRDB_DEBUG !== true) return;
        $registry_key = $class_name . '::' . $method_name;
        try {
            throw new \Exception($registry_key);
        } catch(\Exception $e) {
            $trace = array_map(function($item) {
                if (!isset($item['file'])) return 'N/A';
                return $item['file'] . " " . $item['line'];
            }, $e->getTrace());

            self::add($registry_key, $trace);
        }
    }

    public static function show() {
        foreach(self::$registry as $key => $traces) {
            echo "\n## $key\n";
            foreach ($traces as $idx => $trace) {
                echo "\n  -- Trace #$idx --\n";
                foreach($trace as $line => $details) {
                    printf("%02d %s\n", $line, $details);
                }
            }
        }
    }
}