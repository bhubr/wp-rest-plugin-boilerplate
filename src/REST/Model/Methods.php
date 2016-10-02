<?php
namespace bhubr\REST\Model;

interface Methods {
    public static function create($payload);
    public static function _create($type, $payload);
    public static function read($object_id);
    public static function _read($type, $object_id);
    public static function update($object_id, $payload);
    public static function _update($type, $object_id, $payload);
    public static function delete($object_id);
    public static function _delete($type, $object_id);
    public static function read_all($extra_args = array());
}