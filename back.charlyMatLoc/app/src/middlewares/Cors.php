<?php

namespace charlymatloc\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpUnauthorizedException;

class Cors implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private int $maxAge;
    private bool $allowCredentials;
    private bool $strictMode;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        int $maxAge = 3600,
        bool $allowCredentials = true,
        bool $strictMode = false
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->maxAge = $maxAge;
        $this->allowCredentials = $allowCredentials;
        $this->strictMode = $strictMode;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $this->determineAllowedOrigin($request);

        // Mode strict : vérifier la présence du header Origin
        if ($this->strictMode && !$request->hasHeader('Origin')) {
            throw new HttpUnauthorizedException(
                $request,
                "missing Origin Header (cors)"
            );
        }

        // Gérer la requête préflight OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            return $this->addCorsHeaders($response, $request, $origin);
        }

        try {
            // Appel normal de la route
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            // Même en cas d'erreur, renvoyer les headers CORS
            $response = new \Slim\Psr7\Response(500);
            $response->getBody()->write($e->getMessage());
        }

        return $this->addCorsHeaders($response, $request, $origin);
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->process($request, $handler);
    }

    private function determineAllowedOrigin(ServerRequestInterface $request): string
    {
        $requestOrigin = $request->hasHeader('Origin') ? $request->getHeaderLine('Origin') : '';

        // Si '*' est autorisé et pas de credentials, retourner '*'
        if (in_array('*', $this->allowedOrigins) && !$this->allowCredentials) {
            return '*';
        }

        // Si l'origine de la requête est autorisée
        if (in_array('*', $this->allowedOrigins) || in_array($requestOrigin, $this->allowedOrigins)) {
            return $requestOrigin ?: '*';
        }

        // Par défaut, retourner la première origine autorisée
        return $this->allowedOrigins[0] ?? '*';
    }

    private function addCorsHeaders(ResponseInterface $response, ServerRequestInterface $request, string $origin): ResponseInterface
    {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);

        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                implode(', ', $this->allowedMethods)
            );

            $requestedHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $headersToAllow = $requestedHeaders ?: implode(', ', $this->allowedHeaders);
            $response = $response->withHeader('Access-Control-Allow-Headers', $headersToAllow);
            $response = $response->withHeader('Access-Control-Max-Age', (string)$this->maxAge);
        }

        $response = $response->withHeader(
            'Access-Control-Expose-Headers',
            'Content-Length, X-Request-ID'
        );

        return $response;
    }
}
