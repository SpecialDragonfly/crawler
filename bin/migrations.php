<?php
require_once __DIR__.'/../vendor/autoload.php';

$db = new SQLite3('data.sqlite');

$db->exec("CREATE TABLE IF NOT EXISTS page_links (page TEXT, parent_page TEXT, domain TEXT)");