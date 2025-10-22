<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\service;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\service\charlymatlocAuthnServiceInterface;
use charlymatloc\core\ports\api\service\AuthenticationFailedException;
use charlymatloc\core\ports\spi\AuthRepositoryInterface;

class CharlymatlocAuthnService implements CharlymatlocAuthnServiceInterface
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function byCredentials(CredentialsDTO $credentials): ProfileDTO
    {
        $userData = $this->authRepository->findUserByEmail($credentials->email);

        if ($userData === null) {
            throw new AuthenticationFailedException('Invalid credentials');
        }

        if (!password_verify($credentials->password, $userData['password'])) {
            throw new AuthenticationFailedException('Invalid credentials');
        }

        return new ProfileDTO(
            $userData['id'],  
            $userData['email'],
            (int)$userData['role']
        );
    }

    public function register(CredentialsDTO $credentials, int $role): ProfileDTO
    {
        throw new \RuntimeException('Registration not yet implemented');
    }
}
