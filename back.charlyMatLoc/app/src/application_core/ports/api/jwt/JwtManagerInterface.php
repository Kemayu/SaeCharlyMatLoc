<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\jwt;

use charlymatloc\core\ports\api\dto\ProfileDTO;

/**
 * Interface JwtManagerInterface
 * Gère la création et validation des tokens JWT
 */
interface JwtManagerInterface
{
    public const ACCESS_TOKEN = 1;
    public const REFRESH_TOKEN = 2;

    /**
     * Crée un token JWT
     * @param ProfileDTO $profile Le profil utilisateur
     * @param int $type Type de token (ACCESS_TOKEN ou REFRESH_TOKEN)
     * @return string Le token JWT signé
     */
    public function create(ProfileDTO $profile, int $type): string;

    /**
     * Valide et décode un token JWT
     * @param string $token Le token à valider
     * @return ProfileDTO Le profil extrait du token
     * @throws JwtManagerExpiredTokenException Si le token a expiré
     * @throws JwtManagerInvalidTokenException Si le token est invalide
     */
    public function validate(string $token): ProfileDTO;
}
