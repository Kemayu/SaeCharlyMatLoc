<?php
declare(strict_types=1);

namespace charlymatloc\core\application\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\service\AuthzServiceInterface;

class AuthzMiddleware implements MiddlewareInterface
{
    private AuthzServiceInterface $authzService;

    public function __construct(AuthzServiceInterface $authzService)
    {
        $this->authzService = $authzService;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        /** @var ProfileDTO|null $user */
        $user = $request->getAttribute('authenticated_user');

        if ($user === null) {
            return $this->forbidden('User not authenticated');
        }

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        
        if ($route === null) {
            return $this->forbidden('Route not found');
        }

        $routeName = $route->getName();
        $routeArgs = $route->getArguments();

        if (!$this->checkAuthorization($user, $routeName, $routeArgs)) {
            return $this->forbidden('Access denied');
        }

        return $handler->handle($request);
    }

    // Appelle la méthode du service d'autorisation selon le nom de la route
    private function checkAuthorization(ProfileDTO $user, ?string $routeName, array $routeArgs): bool
    {
        return match ($routeName) {
            // Routes du panier
            'cart.get' => $this->authzService->canAccessCart(
                $user,
                $routeArgs['userId'] ?? ''
            ),
            
            'cart.items.create' => $this->authzService->canAddToCart($user),
            
            'cart.items.delete' => $this->authzService->canRemoveFromCart(
                $user,
                $routeArgs['itemId'] ?? ''
            ),
            
            // Routes des réservations
            'reservations.list' => $this->authzService->canAccessReservations(
                $user,
                $routeArgs['userId'] ?? ''
            ),
            
            'reservations.create' => $this->authzService->canValidateCart($user), // Créer réservation = valider panier
            
            'reservations.get' => $this->authzService->canAccessReservation(
                $user,
                $routeArgs['id'] ?? ''
            ),
            
            default => false,
        };
    }

    private function forbidden(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
