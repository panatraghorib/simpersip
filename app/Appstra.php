<?php
namespace App;

use App\Models\Role;
use App\Models\User;
use App\Models\Config;
use App\Models\Permission;

class Appstra
{

    protected $models = [
        'Config' => Config::class,
        'User' => User::class,
        'Role' => Role::class,
        'Permission' => Permission::class,
    ];

    protected $supportedComponent = [
        'text',
        'email',
        'password',
        'textarea',
        'checkbox',
        'search',
        'number',
        'url',
        'time',
        'date',
        'datetime',
        'select',
        'select_multiple',
        'radio',
        'switch',
        'slider',
        'editor',
        'tags',
        'color_picker',
        'upload_image',
        'upload_image_multiple',
        'upload_file',
        'upload_file_multiple',
        'hidden',
        'code',
        'relation',
    ];

    public function getDefaultJwtTokenLifetime()
    {
        return 60 * 24; // a day
    }

    public function getConfig($key, $defaultValue = null)
    {
        $value = config($key);
        if (is_null($value) || $value == '') {
            return $defaultValue;
        }

        return $value;
    }

    public static function getAppTablePrefix()
    {
        return config('appstra.database.prefix');
    }
}
