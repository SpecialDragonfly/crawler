<?php

require __DIR__.'/../vendor/autoload.php';

use Crawler\Console\CrawlCommand;
use Crawler\Service\Crawler;
use Crawler\Service\Repository;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;

$httpClient = new Client();
$monolog = new Logger("Crawler");
$monolog->pushHandler(new StreamHandler("log.txt"));

$db = new SQLite3('data.sqlite');
$db->busyTimeout(5000);
$repository = new Repository($db);

$application = new Application();
$application->add(new CrawlCommand(new Crawler($monolog, $httpClient, $repository)));

try {
    $application->run();
    $db->close();
} catch (Exception $e) {
    echo "Something went wrong: ".$e->getMessage();
}

