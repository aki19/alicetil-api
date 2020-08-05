<?php

namespace App\Libraries;

if (!defined("__PASSWORD_DEFAULT_LENGTH")) define("__PASSWORD_DEFAULT_LENGTH", 8);
if (!defined("__PASSWORD_STRETCHING")) define("__PASSWORD_STRETCHING", 1000);
if (!defined("__PASSWORD_COMMON_SALT")) define("__PASSWORD_COMMON_SALT", env("PASSWORD_COMMON_SALT"));

class HashUtil {

    public static function stretching($string, $salt = __PASSWORD_COMMON_SALT) {

        for ($i = 0; $i < __PASSWORD_STRETCHING; $i++) {
            $string = hash("sha256", $salt . $string . __PASSWORD_COMMON_SALT);
        }
        return $string;

    }

}
