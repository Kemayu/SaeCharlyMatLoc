<?php

use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\core\ports\spi\ServiceCartInterface;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use charlymatloc\core\application\ports\api\ServicePaymentInterface;
use charlymatloc\core\ports\spi\CartRepositoryInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ReservationRepositoryInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\PaymentRepositoryInterface;
use charlymatloc\core\ports\spi\AuthRepositoryInterface;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use charlymatloc\core\ports\api\service\CharlymatlocAuthnServiceInterface;
use charlymatloc\core\ports\api\service\CharlymatlocAuthnService;
use charlymatloc\core\ports\api\service\AuthzServiceInterface;
use charlymatloc\core\ports\api\service\AuthzService;
use charlymatloc\infra\repositories\PDOToolRepository;
use charlymatloc\infra\repositories\PDOCartRepository;
use charlymatloc\infra\repositories\PDOReservationRepository;
use charlymatloc\infra\repositories\PDOPaymentRepository;
use charlymatloc\infra\repositories\PDOAuthRepository;
use charlymatloc\infra\provider\JwtAuthProvider;
use charlymatloc\infra\jwt\JwtManager;
use Psr\Container\ContainerInterface;
use charlymatloc\core\application\usecases\ServiceTool;
use charlymatloc\core\application\usecases\ServiceCart;
use charlymatloc\core\application\usecases\ServiceReservation;
use charlymatloc\core\application\usecases\ServicePayment;

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

    // Service Réservation
    ServiceReservationInterface::class => function (ContainerInterface $c) {
        return new ServiceReservation(
            $c->get(ReservationRepositoryInterface::class),
            $c->get(ServiceCartInterface::class)
        );
    },

    ServicePaymentInterface::class => function (ContainerInterface $c) {
        return new ServicePayment(
            $c->get(ReservationRepositoryInterface::class),
            $c->get(PaymentRepositoryInterface::class)
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

    ReservationRepositoryInterface::class => fn(ContainerInterface $c) => new PDOReservationRepository($c->get('charlymatloc.pdo')),

    PaymentRepositoryInterface::class => fn(ContainerInterface $c) => new PDOPaymentRepository($c->get('charlymatloc.pdo')),

    AuthRepositoryInterface::class => fn(ContainerInterface $c) => new PDOAuthRepository($c->get('charlymatloc.pdo')),

    // Authentication Service
    CharlymatlocAuthnServiceInterface::class => function (ContainerInterface $c) {
        return new CharlymatlocAuthnService(
            $c->get(AuthRepositoryInterface::class)
        );
    },

    // JWT Manager
    JwtManager::class => function (ContainerInterface $c) {
        return new JwtManager(
            $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production',
            $_ENV['JWT_ISSUER'] ?? 'charlymatloc-api',
            $_ENV['JWT_ALGORITHM'] ?? 'HS512',
            (int)($_ENV['JWT_ACCESS_DURATION'] ?? 3600),
            (int)($_ENV['JWT_REFRESH_DURATION'] ?? 86400)
        );
    },

    // Auth Provider
    AuthProviderInterface::class => function (ContainerInterface $c) {
        return new JwtAuthProvider(
            $c->get(CharlymatlocAuthnServiceInterface::class),
            $c->get(JwtManager::class)
        );
    },

    // Authorization Service
    AuthzServiceInterface::class => function (ContainerInterface $c) {
        return new AuthzService(
            $c->get(ReservationRepositoryInterface::class)
        );
    },
];
