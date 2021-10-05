<?php
define("SUCCESS", 0);
define("E_NOT_LOGGED_IN", -1);
define("E_INVALID_CREDENTIALS", -2);
define("E_UNAUTHORIZED", -3);
define("E_DISABLED", -4);
define("E_ACTIVATED", -5);
define("E_DOESNT_EXIST", -6);
define("E_EMAIL_SUBSYSTEM_DISABLED", -7);
define("E_ADMIN_INVITE_ONLY", -8);
define("E_INVALID_TOKEN", -9);
define("E_USE_EMAIL_SUBSYSTEM", -10);
define("E_MAX_COUNT", -11);
define("E_EMPTY", -12);
define("E_LOCAL_SHARING_DISABLED", -13);
define("E_REMOTE_SHARING_DISABLED", -14);

define("E_ONLY_POST", -50);
define("E_FIELD_NOT_SET", -51);
define("E_FIELD_INVALID", -52);
define("E_MAIL_FAILED", -53);
define("E_FILTER_INVALID", -54);
define("E_PASSWORD_MISSMATCH", -55);
define("E_JSON_DECODE", -100);
define("E_JSON_ENCODE", -101);

define('MYSQL_ERROR', 1644); // Unhandled user-defined exception condition
define('CURL_ERROR', -1000); // General error for CURL calls

?>
