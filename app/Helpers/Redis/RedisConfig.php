<?php
namespace App\Helpers\Redis;

use App\Models\Config;
use Illuminate\Support\Facades\Redis;

class RedisConfig
{
    public static $appRedisConfigKey = 'redis_config_key';

    public static function save()
    {
        $modelConfiguration = Config::all();
        try {
            Redis::set(self::$appRedisConfigKey, serialize($modelConfiguration));
        } catch (\Exception$th) {
            throw $th;
        }
    }

    public static function testRedis()
    {
        try {
            // Create a Redis Instance
            // $redis = new \Redis();

            // Try to connect to a redis server
            // In this case within the host machine and the default port of redis
            Redis::connect('127.0.0.1', 6379);

            // Define some Key
            Redis::set('user', 'sdkcarlos');

            // Obtain value
            $user = Redis::get('user');

            // Should Output: sdkcarlos
            print($user);
        } catch (\Exception$ex) {echo $ex->getMessage();
        }

    }

    public static function get()
    {
        // dump(Redis::connect('127.0.0.1', 6379));
        // Redis::set('user', 'sdkcarlos');

        $result = [];
        try {
            $configFromRedis = Redis::get(self::$appRedisConfigKey);
            $result = unserialize($configFromRedis);
        } catch (\Exception$th) {
            $result = Config::all();
        }

        return $result;
    }
}
