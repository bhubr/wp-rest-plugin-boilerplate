<?php
namespace bhubr\REST\Utils;

class Collection extends \Illuminate\Support\Collection {

    /**
     * Get item at offset $key or fail
     * @param $key string Key of the item to fetch
     */
    public function get_f($key) {
        if (! $this->has($key)) {
            throw new \Exception("Key '$key' not found in collection");
        }
        return $this->get($key);
    }
}