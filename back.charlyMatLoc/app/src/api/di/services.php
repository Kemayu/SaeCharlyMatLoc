<?php

use DI\Container;
use App\Infrastructure\Repositories\ToolRepository;
use App\ApplicationCore\Port\ToolRepositoryInterface;
use App\ApplicationCore\Application\UseCases\GetToolDetails;
use App\Infrastructure\Action\GetToolDetailsAction;
use PDO;

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
};
