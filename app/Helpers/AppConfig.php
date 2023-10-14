<?php
namespace App\Helpers;

use App\Models\Config;

class AppConfig {

    public static function get($key) {
        $config = Config::where('key', $key)->first();

        if($config) {
            return $config->value;
        } else return null;
    }
}
