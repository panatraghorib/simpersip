<?php
namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class ConvertCase
{
    public static function snake($dt)
    {
        if (is_string($dt)) {
            return self::convertString($dt, 'SNAKE');
        } elseif (is_array($dt)) {
            return self::convertArray($dt, 'SNAKE');
        } elseif (is_object($dt)) {
            return self::convertObject($dt, 'SNAKE');
        }

        return $dt;
    }

    public static function pascal($dt)
    {
        if (is_string($dt)) {
            return self::convertString($dt, 'PASCAL');
        } elseif (is_array($dt)) {
            return self::convertArray($dt, 'PASCAL');
        } elseif (is_object($dt)) {
            return self::convertObject($dt, 'PASCAL');
        }

        return $dt;
    }

    public static function camel($dt)
    {
        if (is_string($dt)) {
            return self::convertString($dt);
        } elseif (is_array($dt)) {
            return self::convertArray($dt);
        } elseif (is_object($dt)) {
            return self::convertObject($dt);
        }

        return $dt;
    }

    private static function convertString($param, $type = 'CAMEL')
    {
        $result = '';
        switch ($type) {
            case 'SNAKE':
                $strings = str_split($param, 1);
                foreach ($strings as $key => $value) {
                    if ($key > 0) {
                        //Returns true if every character in text is an uppercase
                        //letter in the current locale. When called with an empty
                        //string the result will always be false.
                        if (ctype_upper($value)) {
                            $result = $result . '_' . strtolower($value);
                        } else {
                            $result = $result . '' . $value;
                        }
                    } else {
                        $result = $result . '' . strtolower($value);
                    }
                }
                break;
            case 'PASCAL':
                $strings = explode('_', $param);
                foreach ($strings as $key => $value) {
                    $result = $result . '' . ucfirst($value);
                }
                break;
            default:
                $strings = explode('_', $param);
                foreach ($strings as $key => $value) {
                    if ($key > 0) {
                        $result = $result . '' . ucfirst($value);
                    } else {
                        $result = $result . '' . $value;
                    }
                }
        }

        return $result;
    }

    private static function convertObject($param, $type = 'CAMEL')
    {
        if ($param instanceof Model) {
            $param = $param->getAttributes();
        }
        $keys = array_keys((array) $param);
        $object = ['true'];
        foreach ($keys as $key => $value) {
            if (is_array(((array) $param)[$value])) {
                $object[self::convertString($value, $type)] = self::convertArray(((array) $param)[$value], $type);
            } elseif (is_object(((array) $param)[$value])) {
                $object[self::convertString($value, $type)] = self::convertObject(((array) $param)[$value], $type);
            } else {
                $object[self::convertString($value, $type)] = ((array) $param)[$value];
            }
        }

        return $object;
    }

    private static function convertArray($param, $type = 'CAMEL')
    {
        $objects = [];
        foreach ($param as $key => $object) {
            if (is_object($object)) {
                $objects[self::convertString($key, $type)] = self::convertObject($object, $type);
            } elseif (is_array($object)) {
                $objects[self::convertString($key, $type)] = self::convertArray($object, $type);
            } else {
                $objects[self::convertString($key, $type)] = $object;
            }
        }

        return $objects;
    }
}
