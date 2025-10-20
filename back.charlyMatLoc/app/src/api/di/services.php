<?php

use DI\Container;
use App\Infrastructure\Repositories\ToolRepository;
use App\ApplicationCore\Port\ToolRepositoryInterface;
use App\ApplicationCore\Application\UseCases\GetToolDetails;
use App\Infrastructure\Action\GetToolDetailsAction;
use PDO;
use App\Domain\Entity\Cart;
use App\ApplicationCore\Application\UseCases\GetCartDetails;
use App\Infrastructure\Action\GetCartDetailsAction;

return function (Container $container): void {
    // Database connection
    $container->set(PDO::class, function () use ($container) {
        $settings = $container->get('settings')['db'];
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $settings['host'], $settings['port'], $settings['name']);
        return new PDO($dsn, $settings['user'], $settings['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    });

    // Repository
    $container->set(ToolRepositoryInterface::class, function (Container $c) {
        return new ToolRepository($c->get(PDO::class));
    });

    // Use case
    $container->set(GetToolDetails::class, function (Container $c) {
        return new GetToolDetails($c->get(ToolRepositoryInterface::class));
    });

    // Action
    $container->set(GetToolDetailsAction::class, function (Container $c) {
        return new GetToolDetailsAction($c->get(GetToolDetails::class));
    });

    // Cart entity
    $container->set(Cart::class, function () {
        return new Cart();
    });

    // Use case for cart details
    $container->set(GetCartDetails::class, function (Container $c) {
        return new GetCartDetails($c->get(Cart::class));
    });

    // Action for cart details
    $container->set(GetCartDetailsAction::class, function (Container $c) {
        return new GetCartDetailsAction($c->get(GetCartDetails::class));
    });
};
