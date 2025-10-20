<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use toubilib\api\middlewares\Cors;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ );
$dotenv->load();



$app = AppFactory::create();


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware($c->get('displayErrorDetails'), false, false)
    ->getDefaultErrorHandler()
    ->forceContentType('application/json')
;

$app = (require_once __DIR__ . '/../src/api/routes.php')($app);


return $app;