<?php declare(strict_types=1);

$projectRoot = \dirname(__DIR__, 4);

$loader = require $projectRoot . '/vendor/autoload.php';
$loader->addPsr4('Torq\\Shopware\\CustomPricing\\', __DIR__ . '/../src');
$loader->addPsr4('Torq\\Shopware\\CustomPricing\\Tests\\', __DIR__);
