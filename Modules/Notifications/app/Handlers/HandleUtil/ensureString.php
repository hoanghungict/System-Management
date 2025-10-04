<?php

namespace Modules\Notifications\app\Handlers\HandleUtil;

class EnsureString
{
    public static function ensureString($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        if (is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return '';
        }
        
        return (string)$value;
    }   
}

