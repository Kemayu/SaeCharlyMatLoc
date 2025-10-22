<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use charlymatloc\middlewares\Cors;


$envFile = __DIR__ . '/.env';
$envDist = __DIR__ . '/.env.dist';

try {
    if (file_exists($envFile)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } elseif (file_exists($envDist)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '.env.dist');
        $dotenv->load();
    }
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // continuer sans .env
}

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/settings.php');
$builder->addDefinitions(__DIR__ . '/service.php');
$builder->addDefinitions(__DIR__ . '/api.php');

$c = $builder->build();

$app = AppFactory::createFromContainer($c);

$app->add(Cors::class);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Définit si on affiche les détails d'erreur : priorité .env DISPLAY_ERROR_DETAILS, sinon true pour dev
$displayErrorDetails = true;
$envValue = getenv('DISPLAY_ERROR_DETAILS');
if ($envValue !== false) {
    $displayErrorDetails = filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
}

// Ajout du middleware d'erreurs et remplacement du handler par un handler JSON détaillé (dev only)
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
$responseFactory = $app->getResponseFactory();

$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetailsParam,
    bool $logErrors,
    bool $logErrorDetails
) use ($responseFactory, $displayErrorDetails): ResponseInterface {
    $response = $responseFactory->createResponse();
    $payload = [
        'message' => 'Internal Server Error'
    ];

    if ($displayErrorDetails) {
        $payload['error'] = $exception->getMessage();
        $payload['type'] = get_class($exception);
        $payload['trace'] = $exception->getTraceAsString();
    }

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
};

$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

// Charger/éxécuter les routes (si routes.php retourne une callable, on l'exécute)
$routes = require_once __DIR__ . '/../src/api/routes.php';
if (is_callable($routes)) {
    $app = $routes($app);
}

return $app;
