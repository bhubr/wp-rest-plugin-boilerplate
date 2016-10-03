<?php

use bhubr\REST\Utils\Collection;
use bhubr\REST\Payload\Formatter;
if (! function_exists('collect_f')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \bhubr\REST\Utils\Collection;
     */
    function collect_f($value = null)
    {
        return new Collection($value);
    }
}

if (! function_exists('rpb_build_plugin_descriptor')) {
    /**
     * Create a plugin descriptor for Plugin_Boilerplate
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    function rpb_build_plugin_descriptor($plugin_name, $plugin_dir, $options = [])
    {
        $base_properties = [
            'plugin_name' => $plugin_name,
            'plugin_dir'  => $plugin_dir
        ];
        $default_options = [
            'models_dir' => 'models',
            'models_namespace' => 'bhubr\\',
            'rest_type' => Formatter::JSONAPI,
            'rest_root' => 'bhubr',
            'rest_version' => 1
        ];
        $properties = array_merge($base_properties, $default_options, $options);
        return collect_f($properties);
    }
}