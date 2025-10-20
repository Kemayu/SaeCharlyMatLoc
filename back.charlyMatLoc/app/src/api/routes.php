<?php

declare(strict_types=1);

use charlyMatL\application\actions\GetCatalogAction;
use charlyMatL\application\actions\GetToolDetailsAction;
use Slim\App;

return function(App $app): App {
    // Route pour récupérer le catalogue des outils
    $app->get('/tools', GetCatalogAction::class);

    // Route pour récupérer les détails d'un outil spécifique
    $app->get('/tools/{id}', GetToolDetailsAction::class);

    return $app;
};