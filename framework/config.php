<?php
define('URI_API', 'api');
define('URI_API_BROWSER', 'browse');
define('FOLDER_ROOT', realpath('./..').'/');
define('FOLDER_API', 'api/');
define('FOLDER_FRAMEWORK', 'framework/');
define('FOLDER_DATABASE', 'database/');
define('FOLDER_PUBLIC', 'public/');
define('FOLDER_STORAGE', 'storage/');
define('FOLDER_THEMES', FOLDER_FRAMEWORK.'themes/');

class Config {
    public static $dbDriver = 'sqlite3';
    public static $dbDatabase = 'ntds';
    public static $dbUsername = '';
    public static $dbPassword = '';
    public static $themeName = 'default';
}
