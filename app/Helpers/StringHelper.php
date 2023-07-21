<?php

namespace App\Helpers;

class StringHelper
{
    public static function repair($var)
    {
        $string = stripslashes($var);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        return $string;
    }
}
