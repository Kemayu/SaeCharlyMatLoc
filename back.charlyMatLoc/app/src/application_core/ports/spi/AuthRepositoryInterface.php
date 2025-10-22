<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\spi;

interface AuthRepositoryInterface
{
    /**
     * Récupère un utilisateur par email
     * 
     * @param string $email
     * @return array|null 
     */
    public function findUserByEmail(string $email): ?array;
}
