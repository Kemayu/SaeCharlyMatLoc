<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\service;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\service\CharlymatlocAuthnServiceInterface;
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
        $email = strtolower(trim($credentials->email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        if (mb_strlen($credentials->password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        $existingUser = $this->authRepository->findUserByEmail($email);
        if ($existingUser !== null) {
            throw new \RuntimeException('Email already in use');
        }

        $passwordHash = password_hash($credentials->password, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            throw new \RuntimeException('Unable to hash password');
        }

        $userData = $this->authRepository->createUser($email, $passwordHash, $role);

        return new ProfileDTO(
            $userData['id'],
            $userData['email'],
            (int)$userData['role']
        );
    }
}
