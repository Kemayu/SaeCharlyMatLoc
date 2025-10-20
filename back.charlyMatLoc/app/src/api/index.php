<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Créer le container
$container = new Container();

// Charger les paramètres
$settings = require __DIR__ . '/di/settings.php';
$container->set('settings', $settings);

// Charger les services
(require __DIR__ . '/di/services.php')($container);

// Configurer Slim avec le container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Charger les routes
(require __DIR__ . '/di/routes.php')($app);

// Lancer l'application
$app->run();
