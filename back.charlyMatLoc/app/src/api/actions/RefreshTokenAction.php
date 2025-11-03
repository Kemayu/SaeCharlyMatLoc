<?php
declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use charlymatloc\core\ports\api\provider\AuthProviderExpiredAccessTokenException;
use charlymatloc\core\ports\api\provider\AuthProviderInvalidAccessTokenException;

class RefreshTokenAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();

        if (!isset($body['refreshToken']) || empty($body['refreshToken'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Refresh token is required'
            ]));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $authDTO = $this->authProvider->refresh($body['refreshToken']);

            $response->getBody()->write(json_encode([
                'profile' => [
                    'id' => $authDTO->profile->ID,
                    'email' => $authDTO->profile->email,
                    'role' => $authDTO->profile->role
                ],
                'accessToken' => $authDTO->accessToken,
                'refreshToken' => $authDTO->refreshToken
            ]));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');

        } catch (AuthProviderExpiredAccessTokenException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Refresh token expired. Please sign in again.'
            ]));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');

        } catch (AuthProviderInvalidAccessTokenException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid refresh token'
            ]));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
