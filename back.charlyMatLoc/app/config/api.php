<?php

use charlymatloc\api\actions\GetToolByIdAction;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use Psr\Container\ContainerInterface;
use charlymatloc\middlewares\Cors;


return [
    // Actions
    GetToolByIdAction::class => function (ContainerInterface $c) {
        return new GetToolByIdAction($c->get(ServiceToolInterface::class));
    },

    Cors::class => function (ContainerInterface $c) {

        return new Cors(
            allowedOrigins: ['http://localhost:3000'],  // ['http://localhost:3000']
            allowedMethods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
            maxAge: 3600,  //1h
            allowCredentials: true,
            strictMode: true  // Désactivé pour permettre les tests sans Origin header
        );
    },
];
