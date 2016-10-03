<?php

use bhubr\REST\Utils\Collection;

if (! function_exists('collect_f')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    function collect_f($value = null)
    {
        return new Collection($value);
    }
}