<?php

use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\core\ports\spi\ServiceCartInterface;
use charlymatloc\core\ports\spi\CartRepositoryInterface;
use charlymatloc\infra\repositories\PDOToolRepository;
use charlymatloc\infra\repositories\PDOCartRepository;
use Psr\Container\ContainerInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\application\usecases\ServiceTool;
use charlymatloc\core\application\usecases\ServiceCart;

return [
    // Service Outil
    ServiceToolInterface::class => function (ContainerInterface $c) {
        return new ServiceTool($c->get(ToolRepositoryInterface::class));
    },

    // Service Panier
    ServiceCartInterface::class => function (ContainerInterface $c) {
        return new ServiceCart(
            $c->get(CartRepositoryInterface::class),
            $c->get(ToolRepositoryInterface::class)
        );
    },

    // Infrastructure - PDO
    'charlymatloc.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('charlymatloc.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    // Repositories
    ToolRepositoryInterface::class => fn(ContainerInterface $c) => new PDOToolRepository($c->get('charlymatloc.pdo')),
    
    CartRepositoryInterface::class => fn(ContainerInterface $c) => new PDOCartRepository($c->get('charlymatloc.pdo')),
];
