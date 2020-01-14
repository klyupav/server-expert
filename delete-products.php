<?php
/**
 * Created by PhpStorm.
 * User: klyupav
 * Date: 02.10.18
 * Time: 18:10
 */
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/WP.php';
require __DIR__.'/../wp-config.php';

$start = time();
//$time_limit = PARSER_TIMELIMIT;
$time_limit = 0;
set_time_limit($time_limit);

$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'dbname' => DB_NAME,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'host' => DB_HOST,
    'driver' => PARSER_DB_DRIVER,
    'charset' => DB_CHARSET,
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

$site_url = PARSER_SITE_URL;
$site_root_dir = __DIR__.'/..';

$wp = new \Parser\WP($conn, $site_url, $site_root_dir);

$wp->deleteAllProducts();
