<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use charlymatloc\api\actions\GetCatalogAction;
use charlymatloc\api\actions\GetToolByIdAction;
use charlymatloc\api\actions\GetCartDetailsAction;
use charlymatloc\api\actions\AddToCartAction;
use charlymatloc\api\actions\RemoveFromCartAction;
use charlymatloc\api\actions\CreateReservationAction;
use charlymatloc\api\actions\GetReservationsAction;
use charlymatloc\api\actions\GetReservationByIdAction;
use charlymatloc\api\actions\SigninAction;
use charlymatloc\core\application\middlewares\AuthnMiddleware;
use charlymatloc\core\application\middlewares\AuthzMiddleware;
use Slim\App;

return function(App $app): App {
    
    // ========== AUTHENTIFICATION ==========
    $app->post('/auth/signin', SigninAction::class)
        ->setName('auth.signin');

    // ========== CATALOGUE (Public - Itération 1) ==========
    // GET /tools - Liste tous les outils du catalogue
    $app->get('/tools', GetCatalogAction::class)
        ->setName('tools.list');

    // GET /tools/{id} - Détails d'un outil spécifique
    $app->get('/tools/{id}', GetToolByIdAction::class)
        ->setName('tools.get');

    // ========== PANIER (Authentification requise - Itération 1) ==========
    // GET /users/{userId}/cart - Récupère le panier d'un utilisateur
    $app->get('/users/{userId}/cart', GetCartDetailsAction::class)
        ->setName('cart.get')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // POST /users/{userId}/cart/items - Ajoute un article au panier (collection)
    $app->post('/users/{userId}/cart/items', AddToCartAction::class)
        ->setName('cart.items.create')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // DELETE /users/{userId}/cart/items/{itemId} - Supprime un article du panier
    $app->delete('/users/{userId}/cart/items/{itemId}', RemoveFromCartAction::class)
        ->setName('cart.items.delete')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // ========== RÉSERVATIONS (Authentification requise - Itération 2+) ==========
    // GET /users/{userId}/reservations - Liste les réservations d'un utilisateur
    $app->get('/users/{userId}/reservations', GetReservationsAction::class)
        ->setName('reservations.list')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // POST /users/{userId}/reservations - Crée une réservation (depuis le panier)
    $app->post('/users/{userId}/reservations', CreateReservationAction::class)
        ->setName('reservations.create')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // GET /reservations/{id} - Détails d'une réservation
    $app->get('/reservations/{id}', GetReservationByIdAction::class)
        ->setName('reservations.get')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // ========== CORS Preflight ==========
    $app->options('/{routes:.+}', function (
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    });

    return $app;
};
