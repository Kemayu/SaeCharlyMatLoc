<?php

namespace charlymatloc\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Routing\RouteContext;


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

    public function process(ServerRequestInterface $request,RequestHandlerInterface $handler): ResponseInterface {
        // Mode strict : vérifier la présence du header Origin
        if ($this->strictMode && !$request->hasHeader('Origin')) {
            throw new HttpUnauthorizedException(
                $request,
                "missing Origin Header (cors)"
            );
        }

        $origin = $this->determineAllowedOrigin($request);

        $response = $handler->handle($request);

        $response = $this->addCorsHeaders($response, $request, $origin);

        return $response;
    }

    public function __invoke(ServerRequestInterface $request,RequestHandlerInterface $handler): ResponseInterface {
        return $this->process($request, $handler);
    }

    private function determineAllowedOrigin(ServerRequestInterface $request): string
    {
        // Si '*' est autorisé et pas de credentials, retourner '*'
        if (in_array('*', $this->allowedOrigins) && !$this->allowCredentials) {
            return '*';
        }

        $requestOrigin = $request->hasHeader('Origin')
            ? $request->getHeaderLine('Origin')
            : '';

        // Si '*' est dans la liste ou si l'origine est dans la liste
        if (in_array('*', $this->allowedOrigins) || in_array($requestOrigin, $this->allowedOrigins)) {
            return $requestOrigin ?: '*';
        }

        // Par défaut, retourner la première origine autorisée
        return $this->allowedOrigins[0] ?? '*';
    }


    private function addCorsHeaders(ResponseInterface $response,ServerRequestInterface $request,string $origin): ResponseInterface {
        // Header obligatoire : origine autorisée
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);

        // Si credentials autorisés
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