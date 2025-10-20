<?php

declare(strict_types=1);

use charlyMatL\application\actions\GetCatalogAction;
use charlyMatL\application\actions\GetToolByIdAction;
use Slim\App;

return function(App $app): App {

    // $app->get('/', HomeAction::class);


    $app->get('/tools', GetCatalogAction::class);
    $app->get('/tools/{id}', GetToolByIdAction::class);

    return $app;
};