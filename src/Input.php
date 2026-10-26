<?php

namespace HarborTours;

/** Cleans form input before anything else reads it. */
class Input
{
    public static function clean(array $input)
    {
        $clean = array();
        foreach ($input as $key => $value) {
            if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            $clean[$key] = trim($value);
        }
        return $clean;
    }
}
