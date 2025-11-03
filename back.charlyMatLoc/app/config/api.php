<?php

use charlymatloc\api\actions\GetToolByIdAction;
use charlymatloc\api\actions\GetCatalogAction;
use charlymatloc\api\actions\GetToolAvailabilityAction;
use charlymatloc\api\actions\GetCartDetailsAction;
use charlymatloc\api\actions\AddToCartAction;
use charlymatloc\api\actions\RemoveFromCartAction;
use charlymatloc\api\actions\UpdateCartItemQuantityAction;
use charlymatloc\api\actions\CreateReservationAction;
use charlymatloc\api\actions\GetReservationsAction;
use charlymatloc\api\actions\GetReservationByIdAction;
use charlymatloc\api\actions\ProcessPaymentAction;
use charlymatloc\api\actions\SigninAction;
use charlymatloc\api\actions\RegisterAction;
use charlymatloc\api\actions\RefreshTokenAction;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\ports\spi\ServiceCartInterface;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use charlymatloc\core\application\ports\api\ServicePaymentInterface;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use charlymatloc\core\ports\api\service\AuthzServiceInterface;
use Psr\Container\ContainerInterface;
use charlymatloc\middlewares\Cors;


return [
    // Actions catalogue
    GetCatalogAction::class => fn(ContainerInterface $c) => new GetCatalogAction($c->get(ServiceToolInterface::class)),
    GetToolByIdAction::class => fn(ContainerInterface $c) => new GetToolByIdAction($c->get(ServiceToolInterface::class)),
    GetToolAvailabilityAction::class => fn(ContainerInterface $c) => new GetToolAvailabilityAction($c->get(ToolRepositoryInterface::class)),

    // Actions panier
    GetCartDetailsAction::class => fn(ContainerInterface $c) => new GetCartDetailsAction($c->get(ServiceCartInterface::class)),
    AddToCartAction::class => fn(ContainerInterface $c) => new AddToCartAction($c->get(ServiceCartInterface::class)),
    RemoveFromCartAction::class => fn(ContainerInterface $c) => new RemoveFromCartAction($c->get(ServiceCartInterface::class)),
    UpdateCartItemQuantityAction::class => fn(ContainerInterface $c) => new UpdateCartItemQuantityAction($c->get(ServiceCartInterface::class)),

    // Actions réservation
    CreateReservationAction::class => fn(ContainerInterface $c) => new CreateReservationAction($c->get(ServiceReservationInterface::class)),
    GetReservationsAction::class => fn(ContainerInterface $c) => new GetReservationsAction($c->get(ServiceReservationInterface::class)),
    GetReservationByIdAction::class => fn(ContainerInterface $c) => new GetReservationByIdAction($c->get(ServiceReservationInterface::class)),

    // Paiement simulé
    ProcessPaymentAction::class => fn(ContainerInterface $c) => new ProcessPaymentAction($c->get(ServicePaymentInterface::class)),

    // Authentification
    SigninAction::class => fn(ContainerInterface $c) => new SigninAction($c->get(AuthProviderInterface::class)),
    RegisterAction::class => fn(ContainerInterface $c) => new RegisterAction($c->get(AuthProviderInterface::class)),
    RefreshTokenAction::class => fn(ContainerInterface $c) => new RefreshTokenAction($c->get(AuthProviderInterface::class)),

    // Middleware CORS
    Cors::class => function (ContainerInterface $c) {
        return new Cors(
            allowedOrigins: ['http://docketu.iutnc.univ-lorraine.fr:48210', 'http://localhost:48210'],
            allowedMethods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
            maxAge: 3600,
            allowCredentials: true,
            strictMode: true
        );
    },
];
