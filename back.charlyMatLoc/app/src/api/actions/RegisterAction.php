<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

final class RegisterAction extends AbstractAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Invalid payload');
        }

        $email = isset($data['email']) ? trim((string)$data['email']) : '';
        $password = isset($data['password']) ? (string)$data['password'] : '';
        $passwordConfirmation = isset($data['password_confirmation']) ? (string)$data['password_confirmation'] : null;

        if ($email === '' || $password === '') {
            throw new HttpBadRequestException($request, 'Email and password are required');
        }

        if ($passwordConfirmation !== null && $password !== $passwordConfirmation) {
            throw new HttpBadRequestException($request, 'Passwords do not match');
        }

        $credentials = new CredentialsDTO($email, $password);

        try {
            // create user
            $this->authProvider->register($credentials, 0);
            // Sign in immediately to provide tokens
            $authDTO = $this->authProvider->signin($credentials);

            $payload = [
                'success' => true,
                'message' => 'Account created successfully',
                'auth' => $authDTO->toArray(),
            ];

            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (\RuntimeException $e) {
            // duplicates or custom runtime errors -> 400
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Unexpected error during registration'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
