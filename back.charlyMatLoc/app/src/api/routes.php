<?php


declare(strict_types=1);

use App\Infrastructure\Action\GetCartDetailsAction;
use charlymatloc\api\actions\GetCatalogAction;
use charlymatloc\api\actions\GetToolByIdAction;
use Slim\App;

return function(App $app): App {
    // Route pour récupérer le catalogue des outils
    $app->get('/tools', GetCatalogAction::class);

    // Route pour récupérer les détails d'un outil spécifique
    $app->get('/tools/{id}', GetToolByIdAction::class);

    // Route pour récupérer les détails du panier
    $app->get('/cart', GetCartDetailsAction::class);

    return $app;
};