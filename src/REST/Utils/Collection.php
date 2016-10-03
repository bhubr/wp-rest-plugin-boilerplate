<?php
namespace bhubr\REST\Utils;

class Collection extends \Illuminate\Support\Collection {

    /**
     * Get item at offset $key or FAIL
     * @param $key string Key of the item to fetch
     */
    public function get_f($key, $custom_msg = '') {
        if (! $this->has($key)) {
            $msg = ! empty($custom_msg) ? $custom_msg :
                "Key '$key' not found in collection";
            throw new \Exception($msg);
        }
        return $this->get($key);
    }
}