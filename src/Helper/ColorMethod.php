<?php

namespace Wfgm5k2d\PhpLightDoc\Helper;

class ColorMethod
{
    public static function getColorMethod(string $method): string
    {
        switch ($method) {
            case 'POST':
                return 'px-2 rounded w-min text-white font-medium bg-yellow-500';
            case 'GET,HEAD':
            case 'GET':
                return 'px-2 rounded w-min text-white font-medium bg-green-500';
            case 'PUT':
                return 'px-2 rounded w-min text-white font-medium bg-blue-500';
            case 'PATCH':
                return 'px-2 rounded w-min text-white font-medium bg-indigo-500';
            case 'DELETE':
                return 'px-2 rounded w-min text-white font-medium bg-red-500';

            default:
                return '';
        }
    }
}

