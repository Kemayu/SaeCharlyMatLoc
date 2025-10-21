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
            allowedOrigins: ['http://docketu.iutnc.univ-lorraine.fr:48210'],  // ['http://localhost:48210']
            allowedMethods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
            maxAge: 3600,  //1h
            allowCredentials: true,
            strictMode: false  // Désactivé pour permettre les tests sans Origin header
        );
    },
];
