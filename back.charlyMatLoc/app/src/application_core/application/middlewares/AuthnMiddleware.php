<?php
declare(strict_types=1);

namespace charlymatloc\core\application\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use charlymatloc\core\ports\api\provider\AuthProviderExpiredAccessTokenException;
use charlymatloc\core\ports\api\provider\AuthProviderInvalidAccessTokenException;
use Slim\Psr7\Response;

class AuthnMiddleware implements MiddlewareInterface
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorized('Missing Authorization header');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorized('Invalid Authorization format');
        }

        $token = $matches[1];

        try {
            $profile = $this->authProvider->getSignedInUser($token);
            $request = $request->withAttribute('authenticated_user', $profile);

            return $handler->handle($request);
        } catch (AuthProviderExpiredAccessTokenException $e) {
            return $this->unauthorized('Token has expired');
        } catch (AuthProviderInvalidAccessTokenException $e) {
            return $this->unauthorized('Invalid token');
        }
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
