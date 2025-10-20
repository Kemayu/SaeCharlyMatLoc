<?php

use App\Infrastructure\Action\GetToolDetailsAction;
use charlymatloc\api\actions\GetToolByIdAction;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use Psr\Container\ContainerInterface;



return [
    // Actions
    GetToolByIdAction::class => function (ContainerInterface $c) {
        return new GetToolByIdAction($c->get(ServiceToolInterface::class));
    },
];
