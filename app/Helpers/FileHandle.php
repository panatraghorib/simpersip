<?php
namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileHandle {
    public static function handle($dt)
    {
        $objects = [];
        foreach ($dt as $key => $val) {
            if (is_array($val)) {
                $objects[$key] = self::handle($val);
            } else {
                $objects[$key] = self::handleUrl($val);
            }
        }

        return $objects;
    }

    protected static function handleUrl($val)
    {
        // - ?: return true instead return key
        if (stristr($val, 'http://') ?: stristr($val, 'https://')) {
            // dump($val);
            return $val;
        }

        if (preg_match('/^.*\.(jpg|jpeg|gif|svg|ico|tif|tiff|webp|heif|png|bmp)$/i', $val)) {
            return Storage::url($val);
        }

        if (Str::contains($val, config('lfm.folder_categories.file.folder_name').'/')) {
            return str_replace(
                config('lfm.folder_categories.file.folder_name').'/',
                Storage::url('/').config('lfm.folder_categories.file.folder_name').'/',
                $val
            );
        }

        if (Str::contains($val, config('lfm.folder_categories.image.folder_name').'/')) {
            return str_replace(
                config('lfm.folder_categories.image.folder_name').'/',
                Storage::url('/').config('lfm.folder_categories.image.folder_name').'/',
                $val
            );
        }

        return $val;
    }

    public static function normalize($val)
    {
        $objects = [];
        foreach ($val as $key => $value) {
            if (is_array($value)) {
                $objects[$key] = self::normalize($value);
            } else {
                $objects[$key] = self::removeBaseUrl($value);
            }
        }

        return $objects;
    }

    protected static function removeBaseUrl($val)
    {
        if (Str::contains($val, Storage::url('/'))) {
            return Str::remove(Storage::url('/'), $val);
        }

        return $val;
    }
}
