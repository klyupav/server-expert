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
$time_limit = 60;
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

$sql = "SELECT * FROM parseit WHERE parsed != 1";
$stmt = $conn->query($sql);

//Категории (меню) парсить кроме: Серверные платформы, Сетевое оборудование, Прочее.

if ($stmt->rowCount())
{
    $fetches = $stmt->fetchAll();
    foreach ($fetches as $fetch)
    {
        $product_id = $fetch['product_id'];
        $row = $exporter->getProductRow($product_id);
        $product = $exporter->getAllProductInfo($row);
        $conn->query("UPDATE `parseit` SET `parsed` = '1' WHERE `product_id` = {$product_id};");
//        $product['categories'][] = 'Серверные платформы';

        if ("Серверные платформы" == trim($product['categories'][0]))
        {
            continue;
        }
        if ("Сетевое оборудование" == trim($product['categories'][0]))
        {
            continue;
        }
        if ("Прочее" == trim($product['categories'][0]))
        {
            continue;
        }
//        print_r($product);die('net');

        $wp->addProduct([
            'article' => $product['sku'],
            'images' => $product['gallery'],
            'price' => $product['price'] * 0.96,
            'name' => $product['model'],
            'desc' => $product['description'],
            'category' => $product['categories'],
            'attr' => $product['attributes'],
        ]);

//        break;
        if (time() - $start > $time_limit - 10)
        {
            break;
        }
    }
}

$wp->conn->delete('wp_options', ['option_value' => '_transient_wc_term_counts']);
$tree = $wp->updateCategoryTree();