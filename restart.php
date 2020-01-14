<?php
/**
 * Created by PhpStorm.
 * User: klyupav
 * Date: 02.10.18
 * Time: 18:10
 */
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/WP.php';
require __DIR__.'/openCartExporter.php';
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
    'driver' => 'pdo_mysql',
    'charset' => DB_CHARSET,
);

$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
$site_url = PARSER_SITE_URL;
$site_root_dir = __DIR__.'/..';

$wp = new \Parser\WP($conn, $site_url, $site_root_dir);

$connectionParams = array(
    'dbname' => 'click4up_3',
    'user' => 'click4up_3',
    'password' => '30031990',
    'host' => 'click4up.beget.tech',
    'driver' => 'pdo_mysql',
    'charset' => DB_CHARSET,
);
$remoteConn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

$exporter = new \App\ParseIt\export\openCartExporter($remoteConn);

$conn->query('TRUNCATE TABLE `parseit`');

$sql = "SELECT * FROM oc_product";
$stmt = $remoteConn->query($sql);

if ($stmt->rowCount())
{
    $rows = $stmt->fetchAll();
    foreach ($rows as $row)
    {
        if ($row['status'] == 0)
        {
            continue;
        }
        $conn->query("INSERT INTO `parseit` (`product_id`) VALUES ('{$row['product_id']}');");
    }
}
