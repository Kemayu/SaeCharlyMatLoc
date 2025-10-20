<?php

use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\infrastructure\repositories\PDOToolRepository;
use Psr\Container\ContainerInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\application\usecases\ServiceTool;


return [
    // Service Outil
    ServiceToolInterface::class => function (ContainerInterface $c) {
        return new ServiceTool($c->get(ToolRepositoryInterface::class));
    },

    // Infrastructure - PDO
    'charlymatloc.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('charlymatloc.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    // Repository
    ToolRepositoryInterface::class => fn(ContainerInterface $c) => new PDOToolRepository($c->get('charlymatloc.pdo')),
];
