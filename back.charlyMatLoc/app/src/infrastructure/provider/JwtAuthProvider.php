<?php
declare(strict_types=1);

namespace charlymatloc\infra\provider;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\dto\AuthDTO;
use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\provider\AuthProviderInterface;
use charlymatloc\core\ports\api\provider\AuthProviderInvalidCredentialsException;
use charlymatloc\core\ports\api\provider\AuthProviderExpiredAccessTokenException;
use charlymatloc\core\ports\api\provider\AuthProviderInvalidAccessTokenException;
use charlymatloc\core\ports\api\jwt\JwtManagerInterface;
use charlymatloc\core\ports\api\jwt\JwtManagerExpiredTokenException;
use charlymatloc\core\ports\api\jwt\JwtManagerInvalidTokenException;
use charlymatloc\core\ports\api\service\CharlymatlocAuthnServiceInterface;
use charlymatloc\core\ports\api\service\AuthenticationFailedException;

class JwtAuthProvider implements AuthProviderInterface
{
    private CharlymatlocAuthnServiceInterface $authnService;
    private JwtManagerInterface $jwtManager;

    public function __construct(
        CharlymatlocAuthnServiceInterface $authnService,
        JwtManagerInterface $jwtManager
    ) {
        $this->authnService = $authnService;
        $this->jwtManager = $jwtManager;
    }

    public function register(CredentialsDTO $credentials, int $role): ProfileDTO
    {
        return $this->authnService->register($credentials, $role);
    }

    public function signin(CredentialsDTO $credentials): AuthDTO
    {
        try {
            $profile = $this->authnService->byCredentials($credentials);

            $accessToken = $this->jwtManager->create($profile, JwtManagerInterface::ACCESS_TOKEN);
            $refreshToken = $this->jwtManager->create($profile, JwtManagerInterface::REFRESH_TOKEN);

            return new AuthDTO($profile, $accessToken, $refreshToken);
        } catch (AuthenticationFailedException $e) {
            throw new AuthProviderInvalidCredentialsException('Invalid credentials', 0, $e);
        }
    }

    public function getSignedInUser(string $accessToken): ProfileDTO
    {
        try {
            return $this->jwtManager->validate($accessToken);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new AuthProviderExpiredAccessTokenException('Access token expired', 0, $e);
        } catch (JwtManagerInvalidTokenException $e) {
            throw new AuthProviderInvalidAccessTokenException('Invalid access token', 0, $e);
        }
    }
}
